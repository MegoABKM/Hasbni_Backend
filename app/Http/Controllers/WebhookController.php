<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Saas\Models\Payment;
use App\Saas\Models\Plan;
use App\Saas\Models\PromoCode;
use App\Saas\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class WebhookController extends Controller
{
    public function handleStripe(Request $request): JsonResponse
    {
        if (! $this->hasValidStripeSignature($request)) {
            Log::warning('Stripe webhook rejected because its signature was invalid', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->json()->all();
        $type = (string) ($payload['type'] ?? '');
        $object = $payload['data']['object'] ?? null;

        if (! is_array($object)) {
            return response()->json(['status' => 'ignored']);
        }

        return match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            default => response()->json(['status' => 'ignored']),
        };
    }

    /**
     * Record a completed Stripe checkout and activate the tenant subscription.
     * Payment creation is idempotent on Stripe's payment intent/session ID.
     *
     * @param  array<string, mixed>  $session
     */
    private function handleCheckoutCompleted(array $session): JsonResponse
    {
        $userId = $session['client_reference_id'] ?? null;
        $email = data_get($session, 'customer_details.email');

        $user = User::query()
            ->when(
                filled($userId),
                fn ($query) => $query->whereKey($userId),
            )
            ->when(
                blank($userId) && filled($email),
                fn ($query) => $query->where('email', $email),
            )
            ->first();

        if (! $user) {
            Log::error('Stripe checkout webhook could not resolve its tenant', [
                'user_id' => $userId,
                'email' => $email,
            ]);

            return response()->json(['error' => 'User not found'], 404);
        }

        $amountPaid = round(((int) ($session['amount_total'] ?? 0)) / 100, 2);
        $plan = Plan::query()
            ->where('monthly_price', $amountPaid)
            ->orWhere('yearly_price', $amountPaid)
            ->first()
            ?? Plan::query()->where('name', 'Pro')->first();

        if (! $plan) {
            Log::error('Stripe checkout webhook could not resolve a plan', [
                'amount' => $amountPaid,
                'user_id' => $user->getKey(),
            ]);

            return response()->json(['error' => 'Plan not found'], 422);
        }

        $cycle = abs((float) $plan->yearly_price - $amountPaid) < 0.005 ? 'yearly' : 'monthly';
        $daysToAdd = $cycle === 'yearly' ? 365 : 30;
        $transactionId = (string) ($session['payment_intent'] ?? $session['id'] ?? '');
        $stripeSubscriptionId = filled($session['subscription'] ?? null)
            ? (string) $session['subscription']
            : null;
        $stripeCustomerId = filled($session['customer'] ?? null)
            ? (string) $session['customer']
            : null;
        $currency = strtoupper((string) ($session['currency'] ?? 'USD'));

        DB::transaction(function () use (
            $user,
            $plan,
            $cycle,
            $daysToAdd,
            $stripeSubscriptionId,
            $stripeCustomerId,
            $transactionId,
            $amountPaid,
            $currency,
            $session,
        ): void {
            $subscription = Subscription::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'plan_id' => $plan->getKey(),
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'stripe_customer_id' => $stripeCustomerId,
                    'status' => 'active',
                    'billing_cycle' => $cycle,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($daysToAdd),
                ],
            );

            $promoCodeId = null;
            $promoCode = data_get($session, 'discount.coupon.name');

            if (filled($promoCode)) {
                $promo = PromoCode::query()->where('code', $promoCode)->first();

                if ($promo) {
                    $promoCodeId = $promo->getKey();
                    $promo->increment('current_uses');
                }
            }

            Payment::query()->firstOrCreate(
                ['transaction_id' => $transactionId],
                [
                    'user_id' => $user->getKey(),
                    'subscription_id' => $subscription->getKey(),
                    'promo_code_id' => $promoCodeId,
                    'amount' => $amountPaid,
                    'currency' => $currency,
                    'payment_method' => 'stripe',
                    'status' => 'successful',
                    'paid_at' => now(),
                ],
            );
        });

        return response()->json(['status' => 'success']);
    }

    /**
     * Mark the matching local subscription as canceled when Stripe deletes it.
     *
     * @param  array<string, mixed>  $stripeSubscription
     */
    private function handleSubscriptionDeleted(array $stripeSubscription): JsonResponse
    {
        $stripeSubscriptionId = (string) ($stripeSubscription['id'] ?? '');

        $subscription = Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
            ->first();

        if (! $subscription && filled(data_get($stripeSubscription, 'metadata.user_id'))) {
            $subscription = Subscription::query()
                ->where('user_id', data_get($stripeSubscription, 'metadata.user_id'))
                ->latest('id')
                ->first();
        }

        if (! $subscription) {
            Log::warning('Stripe deleted subscription was not found locally', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $endedAt = data_get($stripeSubscription, 'ended_at');

        $subscription->update([
            'status' => 'canceled',
            'ends_at' => filled($endedAt) ? Carbon::createFromTimestamp((int) $endedAt) : now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Store a failed invoice exactly once and optionally suspend its subscription.
     *
     * @param  array<string, mixed>  $invoice
     */
    private function handleInvoicePaymentFailed(array $invoice): JsonResponse
    {
        $stripeSubscriptionId = (string) ($invoice['subscription'] ?? '');
        $stripeCustomerId = (string) ($invoice['customer'] ?? '');

        $subscription = null;

        if (filled($stripeSubscriptionId)) {
            $subscription = Subscription::query()
                ->where('stripe_subscription_id', $stripeSubscriptionId)
                ->with('user')
                ->first();
        } elseif (filled($stripeCustomerId)) {
            $subscription = Subscription::query()
                ->where('stripe_customer_id', $stripeCustomerId)
                ->with('user')
                ->latest('id')
                ->first();
        }

        $user = $subscription?->user;
        $email = data_get($invoice, 'customer_email')
            ?? data_get($invoice, 'customer_details.email');

        if (! $user && filled($email)) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            Log::warning('Stripe failed invoice could not resolve its tenant', [
                'invoice_id' => $invoice['id'] ?? null,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'email' => $email,
            ]);

            return response()->json(['status' => 'ignored']);
        }

        $invoiceId = (string) ($invoice['id'] ?? '');
        $amount = round(((int) ($invoice['amount_due'] ?? $invoice['amount_remaining'] ?? 0)) / 100, 2);
        $reason = data_get($invoice, 'last_payment_error.message')
            ?? 'Stripe invoice payment failed.';

        Payment::query()->firstOrCreate(
            ['transaction_id' => $invoiceId],
            [
                'user_id' => $user->getKey(),
                'subscription_id' => $subscription?->getKey(),
                'amount' => $amount,
                'currency' => strtoupper((string) ($invoice['currency'] ?? 'USD')),
                'payment_method' => 'stripe',
                'status' => 'failed',
                'failure_reason' => $reason,
            ],
        );

        if ($subscription && (bool) config('saas.stripe.suspend_on_payment_failure', false)) {
            $subscription->update([
                'status' => 'canceled',
                'ends_at' => now(),
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    private function hasValidStripeSignature(Request $request): bool
    {
        $secret = (string) config('services.stripe.webhook_secret', '');

        if ($secret === '') {
            return ! app()->isProduction();
        }

        $signatureHeader = $request->header('Stripe-Signature');

        if (! is_string($signatureHeader) || $signatureHeader === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);

            if (is_string($key) && is_string($value) && $key !== '' && $value !== '') {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! is_string($timestamp) || $signatures === [] || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        foreach ($signatures as $signature) {
            if (is_string($signature) && hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
