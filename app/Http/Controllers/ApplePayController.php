<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Saas\Models\Payment;
use App\Saas\Models\Plan;
use App\Saas\Models\Subscription;
use App\Saas\Models\WebhookLog;
use App\Saas\Services\AppleJwsVerifier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class ApplePayController extends Controller
{
    public function __construct(private readonly AppleJwsVerifier $verifier) {}

    public function webhook(Request $request): JsonResponse
    {
        $log = WebhookLog::query()->create([
            'provider' => 'apple',
            'event_type' => 'unknown',
            'payload' => $request->all(),
            'status' => 'pending',
        ]);

        try {
            $payload = $this->verifier->verify((string) $request->input('signedPayload'));
            $this->assertBundleId((string) data_get($payload, 'data.bundleId'));
            $log->update(['event_type' => $payload['notificationType'] ?? 'unknown']);
            $this->processNotification($payload);
            $log->update([
                'status' => 'success',
                'attempts' => $log->attempts + 1,
                'processed_at' => now(),
            ]);

            return response()->json(['status' => 'success']);
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'attempts' => $log->attempts + 1,
                'error_message' => $exception->getMessage(),
            ]);
            Log::error('Apple App Store notification failed', ['error' => $exception->getMessage()]);

            return response()->json(['message' => 'Invalid App Store notification.'], 400);
        }
    }

    public function validateReceipt(Request $request): JsonResponse
    {
        $data = $request->validate(['signed_transaction' => ['required', 'string']]);
        $transaction = $this->verifier->verify($data['signed_transaction']);
        $this->assertBundleId((string) ($transaction['bundleId'] ?? ''));
        $expiresAt = Carbon::createFromTimestampMs((int) ($transaction['expiresDate'] ?? 0));

        if ($expiresAt->isPast()) {
            return response()->json(['message' => 'App Store subscription has expired.'], 422);
        }

        $subscription = DB::transaction(fn (): Subscription => $this->activate(
            $transaction,
            (int) $request->user()->getKey(),
        ));

        return response()->json([
            'success' => true,
            'subscription_id' => $subscription->getKey(),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function processNotification(array $payload): void
    {
        $type = (string) ($payload['notificationType'] ?? '');
        $signedTransaction = data_get($payload, 'data.signedTransactionInfo');

        if (! is_string($signedTransaction) || $signedTransaction === '') {
            throw new RuntimeException('Apple notification is missing transaction information.');
        }

        $transaction = $this->verifier->verify($signedTransaction);
        $this->assertBundleId((string) ($transaction['bundleId'] ?? data_get($payload, 'data.bundleId', '')));
        $originalId = (string) ($transaction['originalTransactionId'] ?? '');
        $subscription = Subscription::query()
            ->where('provider', 'apple')
            ->where('provider_original_transaction_id', $originalId)
            ->first();

        if (! $subscription) {
            throw new RuntimeException('No local subscription matches the Apple transaction.');
        }

        DB::transaction(function () use ($type, $transaction, $subscription, $payload): void {
            match ($type) {
                'DID_RENEW' => $this->activate($transaction, (int) $subscription->user_id),
                'DID_FAIL_TO_RENEW' => $this->markRenewalFailed($subscription, $transaction, $payload),
                'EXPIRED' => $subscription->update([
                    'status' => 'expired',
                    'payment_failed_at' => now(),
                    'grace_period_ends_at' => now()->addDays(7),
                    'is_locked' => false,
                ]),
                'REFUND', 'REVOKE' => $this->revoke($subscription, $transaction),
                default => null,
            };
        });
    }

    /** @param array<string, mixed> $transaction */
    private function activate(array $transaction, int $userId): Subscription
    {
        $productId = (string) ($transaction['productId'] ?? '');
        $originalTransactionId = (string) ($transaction['originalTransactionId'] ?? '');
        $transactionId = (string) ($transaction['transactionId'] ?? '');

        if ($productId === '' || $originalTransactionId === '' || $transactionId === '') {
            throw new RuntimeException('Apple transaction is missing required identifiers.');
        }

        $existingOwner = Subscription::query()
            ->where('provider', 'apple')
            ->where('provider_original_transaction_id', $originalTransactionId)
            ->value('user_id');

        if ($existingOwner !== null && (int) $existingOwner !== $userId) {
            throw new RuntimeException('This Apple purchase is already assigned to another tenant.');
        }

        $plan = Plan::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (Plan $plan): bool => str_contains(strtolower($productId), strtolower($plan->name)));

        if (! $plan) {
            throw new RuntimeException("No active plan maps to Apple product {$productId}.");
        }

        $expiresAt = Carbon::createFromTimestampMs((int) $transaction['expiresDate']);

        $subscription = Subscription::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'plan_id' => $plan->getKey(),
                'provider' => 'apple',
                'provider_original_transaction_id' => $originalTransactionId,
                'auto_renews' => true,
                'status' => 'active',
                'billing_cycle' => str_contains(strtolower($productId), 'year') ? 'yearly' : 'monthly',
                'starts_at' => Carbon::createFromTimestampMs((int) ($transaction['purchaseDate'] ?? now()->valueOf())),
                'ends_at' => $expiresAt,
                'grace_period_ends_at' => null,
                'payment_failed_at' => null,
                'dunning_last_notified_day' => null,
                'is_locked' => false,
            ],
        );

        Payment::query()->firstOrCreate(
            [
                'payment_method' => 'apple',
                'transaction_id' => $transactionId,
            ],
            [
                'user_id' => $userId,
                'subscription_id' => $subscription->getKey(),
                'amount' => isset($transaction['price'])
                    ? round(((int) $transaction['price']) / 1000, 2)
                    : ($subscription->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price),
                'currency' => (string) ($transaction['currency'] ?? 'USD'),
                'status' => 'successful',
                'paid_at' => now(),
            ],
        );

        return $subscription;
    }

    /** @param array<string, mixed> $transaction */
    private function revoke(Subscription $subscription, array $transaction): void
    {
        $subscription->update([
            'status' => 'canceled',
            'ends_at' => now(),
            'grace_period_ends_at' => null,
            'is_locked' => true,
        ]);

        Payment::query()
            ->where('payment_method', 'apple')
            ->where('transaction_id', $transaction['transactionId'] ?? null)
            ->update(['status' => 'refunded', 'refunded_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @param  array<string, mixed>  $payload
     */
    private function markRenewalFailed(Subscription $subscription, array $transaction, array $payload): void
    {
        $subscription->update([
            'status' => 'expired',
            'payment_failed_at' => now(),
            'grace_period_ends_at' => now()->addDays(7),
            'dunning_last_notified_day' => null,
            'is_locked' => false,
        ]);

        $identifier = implode('-', [
            'apple-failed',
            (string) ($transaction['originalTransactionId'] ?? $subscription->getKey()),
            (string) ($payload['signedDate'] ?? now()->valueOf()),
        ]);

        Payment::query()->firstOrCreate(
            [
                'payment_method' => 'apple',
                'transaction_id' => $identifier,
            ],
            [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->getKey(),
                'amount' => 0,
                'currency' => (string) ($transaction['currency'] ?? 'USD'),
                'status' => 'failed',
                'failure_reason' => 'Apple subscription renewal failed.',
            ],
        );
    }

    private function assertBundleId(string $bundleId): void
    {
        $expected = (string) config('services.apple.bundle_id');

        if ($expected === '' || ! hash_equals($expected, $bundleId)) {
            throw new RuntimeException('Apple bundle identifier does not match this application.');
        }
    }
}
