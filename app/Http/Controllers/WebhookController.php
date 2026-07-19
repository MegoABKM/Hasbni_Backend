<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleStripe(Request $request)
    {
        if (! $this->hasValidStripeSignature($request)) {
            Log::warning('Stripe Webhook: invalid signature', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->all();

        // التأكد من أن العملية هي عملية دفع ناجحة
        if (isset($payload['type']) && $payload['type'] === 'checkout.session.completed') {
            $session = $payload['data']['object'];

            // جلب ID المستخدم الذي أرسلناه من تطبيق فلاتر
            $userId = $session['client_reference_id'] ?? null;
            $email = $session['customer_details']['email'] ?? null;

            $user = User::where('id', $userId)->orWhere('email', $email)->first();

            if (!$user) {
                Log::error('Stripe Webhook: User not found', ['email' => $email]);
                return response()->json(['error' => 'User not found'], 404);
            }

            // المبلغ المدفوع (Stripe يرسل المبلغ بالسنت، لذلك نقسم على 100)
            $amountPaid = $session['amount_total'] / 100;
            
            // تحديد الباقة بناءً على السعر الذي تم دفعه
            $plan = Plan::where('monthly_price', $amountPaid)->orWhere('yearly_price', $amountPaid)->first();
            if (!$plan) $plan = Plan::where('name', 'Pro')->first(); // باقة افتراضية للحماية

            $cycle = ($amountPaid == $plan->yearly_price) ? 'yearly' : 'monthly';
            $daysToAdd = $cycle === 'yearly' ? 365 : 30;

            // 1. تجديد الاشتراك للمستخدم (أو إنشائه)
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'billing_cycle' => $cycle,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($daysToAdd),
                ]
            );

            // 2. تسجيل الفاتورة لتظهر لك في لوحة تحكم Filament
            
            $promoCodeStr = $session['customer_details']['discount']['coupon']['name'] ?? null; // حسب طريقة تمريرك له في Stripe
$promoCodeId = null;

if ($promoCodeStr) {
    $promo = PromoCode::where('code', $promoCodeStr)->first();
    if ($promo) {
        $promoCodeId = $promo->id;
        // زيادة عدد الاستخدامات
        $promo->increment('current_uses');
    }
}

            Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $user->subscription->id ?? null,
                'promo_code_id' => $promoCodeId,
                'amount' => $amountPaid,
                'currency' => strtoupper($session['currency']),
                'payment_method' => 'stripe',
                'status' => 'successful',
                'transaction_id' => $session['payment_intent'] ?? $session['id'],
                'paid_at' => now(),
            ]);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'ignored']);
    }

    private function hasValidStripeSignature(Request $request): bool
    {
        $secret = env('STRIPE_WEBHOOK_SECRET');

        if (! $secret) {
            return ! app()->isProduction();
        }

        $signatureHeader = $request->header('Stripe-Signature');
        if (! $signatureHeader) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            if ($key && $value) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
