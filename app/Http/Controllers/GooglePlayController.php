<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class GooglePlayController extends Controller
{
    public function verifyPurchase(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string', // اسم الباقة في جوجل بلاي (مثلاً: pro_monthly)
            'purchase_token' => 'required|string', // الإيصال من الهاتف
            'order_id' => 'required|string', // رقم الطلب من جوجل
            'amount' => 'required|numeric', // المبلغ
        ]);

        $user = $request->user();

        // 💡 في تطبيق حقيقي 100%، نقوم هنا بالاتصال بسيرفرات Google للتحقق من الـ purchase_token
        // باستخدام مكتبة google/apiclient. لكن حالياً سنعتمد على أن التطبيق أرسل بيانات صحيحة.

        // 1. التأكد من أن الفاتورة لم تُسجل مسبقاً (حماية من التكرار)
        $existingPayment = Payment::where('transaction_id', $request->order_id)->first();
        if ($existingPayment) {
            return response()->json(['message' => 'Payment already processed.'], 200);
        }

        // 2. تحديد الباقة (نفترض أنك سميت الباقات في جوجل بلاي pro_monthly و pro_yearly)
        $isYearly = str_contains($request->product_id, 'yearly');
        
        // جلب باقة Pro من قاعدة بياناتنا
        $plan = Plan::where('name', 'Pro')->first();
        if (!$plan) {
            return response()->json(['error' => 'Plan not found in database'], 404);
        }

        $cycle = $isYearly ? 'yearly' : 'monthly';
        $daysToAdd = $isYearly ? 365 : 30;

        // 3. تفعيل أو تجديد الاشتراك
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

        // 4. تسجيل الدفعة في لوحة التحكم (Filament)
        Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $user->subscription->id ?? null,
            'amount' => $request->amount,
            'currency' => 'USD', // جوجل بلاي يحول العملات، لكننا سنسجلها بالدولار كمرجع
            'payment_method' => 'google_play', // 👈 طريقة الدفع
            'status' => 'successful',
            'transaction_id' => $request->order_id,
            'paid_at' => now(),
        ]);

        Log::info("User {$user->email} upgraded via Google Play (Order: {$request->order_id}).");

        return response()->json(['success' => true]);
    }
}