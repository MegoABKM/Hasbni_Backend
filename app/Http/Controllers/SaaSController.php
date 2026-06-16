<?php
namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SaaSController extends Controller
{
    public function getPlans()
    {
        $plans = Plan::where('is_active', true)->get();
        // Decode features so Flutter reads it as a proper JSON map
        foreach($plans as $plan) {
            if(is_string($plan->features)) {
                $plan->features = json_decode($plan->features, true);
            }
        }
        return response()->json(['success' => true, 'data' => $plans]);
    }

  public function mySubscription(Request $request)
{
    $user = $request->user()->load('subscription.plan');
    $freePlan = Plan::where('name', 'Free')->first();
    
    // تأكد من فك تشفير الميزات للخطة المجانية
    if ($freePlan && is_string($freePlan->features)) {
        $freePlan->features = json_decode($freePlan->features, true);
    }

    if ($user->subscription && $user->subscription->ends_at && Carbon::parse($user->subscription->ends_at)->isPast()) {
        if ($user->subscription->status !== 'expired') {
            $user->subscription->update(['status' => 'expired']);
        }
    }

    if (!$user->subscription || $user->subscription->status === 'expired') {
        return response()->json([
            'success' => true,
            'is_expired' => true,
            'plan' => $freePlan
        ]);
    }

    $plan = $user->subscription->plan;
    // 🚨 الحل هنا: تأكد من فك التشفير قبل الإرسال 🚨
    if ($plan && is_string($plan->features)) {
        $plan->features = json_decode($plan->features, true);
    }

    return response()->json([
        'success' => true,
        'is_expired' => false,
        'subscription' => $user->subscription,
        'plan' => $plan
    ]);
}

// جلب الإعلان النشط
    public function getActiveAnnouncement()
    {
        $announcement = \App\Models\Announcement::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        return response()->json(['success' => true, 'data' => $announcement]);
    }

    // التحقق من الكوبون
    public function validatePromoCode(\Illuminate\Http\Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $promo = \App\Models\PromoCode::where('code', strtoupper($request->code))->first();

        if (!$promo) {
            return response()->json(['success' => false, 'message' => 'الكوبون غير صحيح.'], 404);
        }

        if (!$promo->is_active) {
            return response()->json(['success' => false, 'message' => 'الكوبون غير فعال.'], 400);
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'عذراً، انتهت صلاحية هذا الكوبون.'], 400);
        }

        if ($promo->max_uses && $promo->current_uses >= $promo->max_uses) {
            return response()->json(['success' => false, 'message' => 'تم تجاوز الحد الأقصى لاستخدام هذا الكوبون.'], 400);
        }

        return response()->json([
            'success' => true,
            'discount_percentage' => $promo->discount_percentage,
            'message' => "تم تطبيق خصم {$promo->discount_percentage}% بنجاح!"
        ]);
    }
}