<?php

declare(strict_types=1);

namespace App\Saas\Services;

use App\Saas\Models\AuditLog;
use App\Saas\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Stripe\StripeClient;

final class PaymentRefundService
{
    public function refund(Payment $payment): Payment
    {
        if ($payment->status !== 'successful') {
            throw new RuntimeException('Only successful payments can be refunded.');
        }

        if (blank($payment->transaction_id)) {
            throw new RuntimeException('The payment does not have a gateway transaction identifier.');
        }

        $refundId = match ($payment->payment_method) {
            'stripe' => $this->refundStripe($payment),
            'myfatoorah' => $this->refundMyFatoorah($payment),
            default => throw new RuntimeException('This payment method does not support direct refunds.'),
        };

        return DB::transaction(function () use ($payment, $refundId): Payment {
            $oldValues = $payment->only(['status', 'refund_id', 'refunded_at']);

            $payment->forceFill([
                'status' => 'refunded',
                'refund_id' => $refundId,
                'refunded_at' => now(),
            ])->save();

            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'event' => 'payment_refunded',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->getKey(),
                'old_values' => $oldValues,
                'new_values' => $payment->only(['status', 'refund_id', 'refunded_at']),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $payment->refresh();
        });
    }

    private function refundStripe(Payment $payment): string
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new RuntimeException('Stripe is not configured.');
        }

        $refund = (new StripeClient($secret))->refunds->create([
            'payment_intent' => $payment->transaction_id,
            'metadata' => ['local_payment_id' => (string) $payment->getKey()],
        ], [
            'idempotency_key' => "payment-refund-{$payment->getKey()}",
        ]);

        return (string) $refund->id;
    }

    private function refundMyFatoorah(Payment $payment): string
    {
        $token = (string) config('services.myfatoorah.token');
        $baseUrl = rtrim((string) config('services.myfatoorah.url'), '/');

        if ($token === '' || $baseUrl === '') {
            throw new RuntimeException('MyFatoorah is not configured.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 250)
            ->post("{$baseUrl}/v2/MakeRefund", [
                'Key' => $payment->transaction_id,
                'KeyType' => 'PaymentId',
                'RefundChargeOnCustomer' => false,
                'ServiceChargeOnCustomer' => false,
                'Amount' => (float) $payment->amount,
                'Comment' => "Refund for payment {$payment->getKey()}",
            ])
            ->throw();

        if ($response->json('IsSuccess') !== true) {
            throw new RuntimeException((string) ($response->json('Message') ?? 'MyFatoorah rejected the refund.'));
        }

        return (string) (
            $response->json('Data.RefundId')
            ?? $response->json('Data.Key')
            ?? $payment->transaction_id
        );
    }
}
