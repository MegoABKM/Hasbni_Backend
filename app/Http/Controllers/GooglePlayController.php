<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Saas\Models\Payment;
use App\Saas\Models\Plan;
use App\Saas\Models\Subscription;
use Carbon\Carbon;
use Google\Client;
use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\SubscriptionPurchaseV2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class GooglePlayController extends Controller
{
    public function verifyPurchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purchase_token' => ['required', 'string', 'max:4096'],
            'product_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $packageName = (string) config('services.google_play.package_name');

            if ($packageName === '') {
                throw new RuntimeException('Google Play package name is not configured.');
            }

            $purchase = $this->publisher()
                ->purchases_subscriptionsv2
                ->get($packageName, $data['purchase_token']);
            $state = (string) $purchase->getSubscriptionState();

            if (! in_array($state, [
                SubscriptionPurchaseV2::SUBSCRIPTION_STATE_SUBSCRIPTION_STATE_ACTIVE,
                SubscriptionPurchaseV2::SUBSCRIPTION_STATE_SUBSCRIPTION_STATE_IN_GRACE_PERIOD,
                SubscriptionPurchaseV2::SUBSCRIPTION_STATE_SUBSCRIPTION_STATE_CANCELED,
            ], true)) {
                return response()->json([
                    'message' => 'Google Play subscription is not entitled.',
                    'state' => $state,
                ], 422);
            }

            $lineItem = collect($purchase->getLineItems())->sortByDesc(
                fn ($item): int => Carbon::parse($item->getExpiryTime())->getTimestamp(),
            )->first();

            if (! $lineItem) {
                throw new RuntimeException('Google Play returned no subscription line items.');
            }

            $expiresAt = Carbon::parse($lineItem->getExpiryTime());

            if ($expiresAt->isPast()) {
                return response()->json(['message' => 'Google Play subscription has expired.'], 422);
            }

            $productId = (string) ($lineItem->getProductId() ?: $data['product_id'] ?? '');
            $isYearly = str_contains(strtolower($productId), 'year');
            $plan = $this->resolvePlan($productId);
            $orderId = (string) ($lineItem->getLatestSuccessfulOrderId() ?: $purchase->getLatestOrderId());
            $autoRenews = (bool) $lineItem->getAutoRenewingPlan()?->getAutoRenewEnabled();

            if ($orderId === '') {
                throw new RuntimeException('Google Play returned no order identifier.');
            }

            $purchaseTokenHash = hash('sha256', $data['purchase_token']);
            $tokenOwner = Subscription::query()
                ->where('provider_purchase_token_hash', $purchaseTokenHash)
                ->value('user_id');

            if ($tokenOwner !== null && (int) $tokenOwner !== (int) $request->user()->getKey()) {
                throw new RuntimeException('This Google Play purchase is already assigned to another tenant.');
            }

            DB::transaction(function () use (
                $request,
                $data,
                $purchase,
                $plan,
                $expiresAt,
                $isYearly,
                $orderId,
                $autoRenews,
                $state,
                $purchaseTokenHash,
            ): void {
                $subscription = Subscription::query()->updateOrCreate(
                    ['user_id' => $request->user()->getKey()],
                    [
                        'plan_id' => $plan->getKey(),
                        'provider' => 'google',
                        'provider_purchase_token' => $data['purchase_token'],
                        'provider_purchase_token_hash' => $purchaseTokenHash,
                        'provider_original_transaction_id' => $orderId,
                        'auto_renews' => $autoRenews,
                        'status' => 'active',
                        'billing_cycle' => $isYearly ? 'yearly' : 'monthly',
                        'starts_at' => Carbon::parse($purchase->getStartTime() ?: now()),
                        'ends_at' => $expiresAt,
                        'grace_period_ends_at' => str_contains($state, 'GRACE')
                            ? now()->addDays(7)
                            : null,
                        'is_locked' => false,
                    ],
                );

                Payment::query()->firstOrCreate(
                    [
                        'payment_method' => 'google_play',
                        'transaction_id' => $orderId,
                    ],
                    [
                        'user_id' => $request->user()->getKey(),
                        'subscription_id' => $subscription->getKey(),
                        'amount' => $isYearly ? $plan->yearly_price : $plan->monthly_price,
                        'currency' => 'USD',
                        'status' => 'successful',
                        'paid_at' => now(),
                    ],
                );
            });

            return response()->json([
                'success' => true,
                'state' => $state,
                'expires_at' => $expiresAt->toIso8601String(),
                'auto_renews' => $autoRenews,
            ]);
        } catch (Throwable $exception) {
            Log::error('Google Play verification failed', [
                'user_id' => $request->user()?->getKey(),
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Google Play verification failed.'], 422);
        }
    }

    private function publisher(): AndroidPublisher
    {
        $credentials = (string) config('services.google_play.credentials');

        if ($credentials === '' || ! is_file($credentials)) {
            throw new RuntimeException('Google Play service-account credentials are not configured.');
        }

        $client = new Client;
        $client->setAuthConfig($credentials);
        $client->setScopes([AndroidPublisher::ANDROIDPUBLISHER]);

        return new AndroidPublisher($client);
    }

    private function resolvePlan(string $productId): Plan
    {
        if ($productId === '') {
            throw new RuntimeException('Google Play returned no product identifier.');
        }

        $normalized = strtolower($productId);

        $plan = Plan::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (Plan $plan): bool => str_contains($normalized, strtolower($plan->name)));

        if (! $plan) {
            throw new RuntimeException("No active plan maps to Google Play product {$productId}.");
        }

        return $plan;
    }
}
