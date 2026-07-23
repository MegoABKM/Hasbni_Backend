<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Saas\Models\Plan;
use App\Saas\Models\Subscription;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforcePlanLimits
{
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($this->isRecoveryOrSyncRequest($request)) {
            return $next($request);
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->subscription()->with('plan')->first();

        if ($subscription !== null && $this->requiresGracePeriod($subscription)) {
            $this->initializeGracePeriod($subscription);
        }

        if ($subscription?->is_locked === true || $this->gracePeriodHasExpired($subscription)) {
            if ($request->isMethodSafe()) {
                return $next($request);
            }

            if ($subscription !== null && ! $subscription->is_locked) {
                $subscription->forceFill(['is_locked' => true])->save();
            }

            return $this->paymentRequired($subscription);
        }

        $plan = $subscription?->plan ?? Plan::query()->where('name', 'Free')->first();

        if ($plan === null) {
            return $next($request);
        }

        if ($feature === 'max_products' && $user->products()->count() >= (int) $plan->max_products) {
            return response()->json([
                'success' => false,
                'message' => 'limit_reached_products',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($feature !== null && ! $user->hasFeature($feature)) {
            return response()->json([
                'success' => false,
                'message' => 'feature_locked',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function requiresGracePeriod(Subscription $subscription): bool
    {
        return $subscription->payment_failed_at !== null
            || $subscription->ends_at?->isPast() === true;
    }

    private function initializeGracePeriod(Subscription $subscription): void
    {
        if ($subscription->grace_period_ends_at !== null) {
            return;
        }

        $startsAt = $subscription->payment_failed_at ?? $subscription->ends_at ?? now();

        $subscription->forceFill([
            'status' => $subscription->ends_at?->isPast() === true ? 'expired' : $subscription->status,
            'grace_period_ends_at' => $startsAt->copy()->addDays(7),
            'is_locked' => false,
        ])->save();
    }

    private function gracePeriodHasExpired(?Subscription $subscription): bool
    {
        return $subscription?->grace_period_ends_at?->isPast() === true;
    }

    private function paymentRequired(?Subscription $subscription): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'subscription_payment_required',
            'subscription' => [
                'status' => $subscription?->status,
                'is_locked' => true,
                'grace_period_ends_at' => $subscription?->grace_period_ends_at?->toIso8601String(),
            ],
        ], Response::HTTP_PAYMENT_REQUIRED);
    }

    private function isRecoveryOrSyncRequest(Request $request): bool
    {
        return $request->is([
            'api/pay/*',
            'api/verify-google-play',
            'api/verify-apple-purchase',
            'api/support/tickets',
            'api/logout',
            'api/fcm-token',
            'api/sync/*',
            'api/*/sync',
            'api/*/sync/*',
        ]);
    }
}
