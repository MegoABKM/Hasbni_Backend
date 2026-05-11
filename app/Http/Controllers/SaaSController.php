<?php
namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

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
        
        // التحقق من التاريخ
        if ($user->subscription && $user->subscription->ends_at->isPast()) {
            $user->subscription->update(['status' => 'expired']);
        }

        if (!$user->subscription || $user->subscription->status === 'expired') {
            return response()->json([
                'success' => true,
                'is_expired' => true, // 👈 إشارة للتطبيق
                'plan' => $freePlan
            ]);
        }

        return response()->json([
            'success' => true,
            'is_expired' => false,
            'subscription' => $user->subscription,
            'plan' => $user->subscription->plan
        ]);
    }
}
