<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. حماية الـ API العام (60 طلب في الدقيقة لكل IP) لمنع هجمات الـ DDoS
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 2. حماية مسار تسجيل الدخول بصرامة (5 محاولات في الدقيقة فقط لكل IP)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // 3. حماية العمليات المالية (منع النقر المزدوج السريع لهندسة ثغرات السباق Race Conditions)
        RateLimiter::for('financial_operations', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
