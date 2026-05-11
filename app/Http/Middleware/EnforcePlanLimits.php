<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Plan;

class EnforcePlanLimits
{
    public function handle(Request $request, Closure $next, $feature = null): Response
    {
        $user = $request->user();
        if (!$user) return $next($request);

        $plan = $user->subscription ? $user->subscription->plan : Plan::where('name', 'Free')->first();
        
        if (!$plan) return $next($request);

        $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features;

        // Example: Block creating too many products
        if ($feature === 'max_products') {
            $productCount = $user->products()->count();
            if ($productCount >= $plan->max_products) {
                return response()->json(['success' => false, 'message' => 'limit_reached_products'], 403);
            }
        }

        // Example: Block features entirely (like cloud sync)
        if ($feature && isset($features[$feature]) && $features[$feature] === false) {
            return response()->json(['success' => false, 'message' => 'feature_locked'], 403);
        }

        return $next($request);
    }
}
