<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Saas\Models\Payment;
use App\Saas\Models\Plan;
use App\Saas\Models\Subscription;
use App\Saas\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MyFatoorahController extends Controller
{
    private readonly string $baseUrl;

    private readonly string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.myfatoorah.url', 'https://apitest.myfatoorah.com'), '/');
        $this->apiKey = (string) config('services.myfatoorah.token', '');
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $user = $request->user();
        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $amount = $validated['cycle'] === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post("{$this->baseUrl}/v2/SendPayment", [
                'NotificationOption' => 'LNK',
                'InvoiceValue' => $amount,
                'CustomerName' => $user->name,
                'DisplayCurrencyIso' => 'USD',
                'CustomerEmail' => $user->email,
                'CallBackUrl' => url('/api/webhooks/myfatoorah/callback'),
                'ErrorUrl' => url('/api/webhooks/myfatoorah/callback'),
                'Language' => app()->getLocale(),
                'UserDefinedField' => "{$user->getKey()}|{$plan->getKey()}|{$validated['cycle']}",
            ]);

        if ($response->successful() && $response->json('IsSuccess') === true) {
            return response()->json([
                'success' => true,
                'payment_url' => $response->json('Data.InvoiceURL'),
            ]);
        }

        Log::error('MyFatoorah checkout initialization failed.', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return response()->json(['success' => false, 'message' => 'payment_gateway_error'], 502);
    }

    public function callback(Request $request): JsonResponse
    {
        $paymentId = (string) $request->query('paymentId', '');
        $log = WebhookLog::query()->create([
            'provider' => 'myfatoorah',
            'event_type' => 'payment.callback',
            'payload' => $request->query(),
            'status' => 'pending',
        ]);

        if ($paymentId === '') {
            $log->update(['status' => 'failed', 'error_message' => 'Missing paymentId.']);

            return response()->json(['error' => 'No payment ID provided'], 400);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(20)
                ->retry(2, 250)
                ->post("{$this->baseUrl}/v2/getPaymentStatus", [
                    'Key' => $paymentId,
                    'KeyType' => 'PaymentId',
                ])
                ->throw();

            $data = $response->json();

            if (! is_array($data) || data_get($data, 'IsSuccess') !== true || data_get($data, 'Data.InvoiceStatus') !== 'Paid') {
                throw new \RuntimeException('MyFatoorah reported an unpaid or canceled invoice.');
            }

            $parts = explode('|', (string) data_get($data, 'Data.UserDefinedField'));
            [$userId, $planId, $cycle] = array_pad($parts, 3, null);

            if (! is_numeric($userId) || ! is_numeric($planId) || ! in_array($cycle, ['monthly', 'yearly'], true)) {
                throw new \RuntimeException('MyFatoorah callback metadata is invalid.');
            }

            DB::transaction(function () use ($paymentId, $data, $userId, $planId, $cycle): void {
                $subscription = Subscription::query()->updateOrCreate(
                    ['user_id' => (int) $userId],
                    [
                        'plan_id' => (int) $planId,
                        'provider' => 'myfatoorah',
                        'status' => 'active',
                        'billing_cycle' => $cycle,
                        'starts_at' => now(),
                        'ends_at' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                        'grace_period_ends_at' => null,
                        'payment_failed_at' => null,
                        'dunning_last_notified_day' => null,
                        'is_locked' => false,
                    ],
                );

                Payment::query()->firstOrCreate(
                    [
                        'payment_method' => 'myfatoorah',
                        'transaction_id' => $paymentId,
                    ],
                    [
                        'user_id' => (int) $userId,
                        'subscription_id' => $subscription->getKey(),
                        'amount' => (float) data_get($data, 'Data.InvoiceValue', 0),
                        'currency' => 'USD',
                        'status' => 'successful',
                        'paid_at' => now(),
                    ],
                );
            });

            $log->update([
                'status' => 'success',
                'processed_at' => now(),
                'attempts' => 1,
            ]);

            return response()->json(['success' => true, 'message' => 'payment_successful']);
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'attempts' => 1,
            ]);

            Log::error('MyFatoorah callback processing failed.', [
                'webhook_log_id' => $log->getKey(),
                'exception' => $exception,
            ]);

            return response()->json(['success' => false, 'message' => 'payment_failed_or_canceled'], 422);
        }
    }
}
