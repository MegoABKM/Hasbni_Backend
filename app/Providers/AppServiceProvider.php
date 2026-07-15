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
        // 1. حماية الـ API العام
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 2. حماية مسار تسجيل الدخول بصرامة
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // 3. حماية العمليات المالية
        RateLimiter::for('financial_operations', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // 4. ربط المراقب (Observer) بالموديلات تلقائياً عبر المسار الكامل
        \App\Models\Product::observe(\App\Observers\SyncObserver::class);
        \App\Models\Sale::observe(\App\Observers\SyncObserver::class);
        \App\Models\Customer::observe(\App\Observers\SyncObserver::class);
        \App\Models\Supplier::observe(\App\Observers\SyncObserver::class);
        \App\Models\Expense::observe(\App\Observers\SyncObserver::class);
        
        // 🚀 تم التعديل هنا إلى الموديل الصحيح في مشروعك
        \App\Models\OwnerWithdrawal::observe(\App\Observers\SyncObserver::class);
    }
}