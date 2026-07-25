<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\OwnerWithdrawal;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\SyncObserver;
use App\Policies\AppConfigPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PlanPolicy;
use App\Policies\RolePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\UserPolicy;
use App\Saas\Models\AppConfig;
use App\Saas\Models\AuditLog;
use App\Saas\Models\Payment;
use App\Saas\Models\Plan;
use App\Saas\Models\Subscription;
use App\Saas\Models\SupportTicket;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Plan::class, PlanPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(AppConfig::class, AppConfigPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

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
        Product::observe(SyncObserver::class);
        Sale::observe(SyncObserver::class);
        Customer::observe(SyncObserver::class);
        Supplier::observe(SyncObserver::class);
        Expense::observe(SyncObserver::class);

        // 🚀 تم التعديل هنا إلى الموديل الصحيح في مشروعك
        OwnerWithdrawal::observe(SyncObserver::class);
    }
}
