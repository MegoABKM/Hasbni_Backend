<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\PurgeTenantDataJob;
use App\Mail\DunningWarningMail;
use App\Models\User;
use App\Policies\PaymentPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\UserPolicy;
use App\Saas\Models\AppConfig;
use App\Saas\Models\Plan;
use App\Saas\Models\Subscription;
use App\Saas\Models\TenantFeatureFlag;
use App\Saas\Models\WebhookLog;
use App\Support\RbacPermission;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class EnterpriseSaasFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_grace_period_soft_locks_writes_but_allows_reads(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $plan = Plan::query()->create([
            'name' => 'Pro',
            'monthly_price' => 29,
            'yearly_price' => 290,
            'features' => ['cloud_sync' => true],
            'max_users' => 5,
            'max_products' => 500,
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'user_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => 'expired',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDays(8),
            'grace_period_ends_at' => now()->subDay(),
            'is_locked' => false,
        ]);

        Route::middleware(['auth:sanctum', 'plan'])->get('/_test/subscription/read', fn () => response()->json(['ok' => true]));
        Route::middleware(['auth:sanctum', 'plan'])->post('/_test/subscription/write', fn () => response()->json(['ok' => true]));
        Sanctum::actingAs($tenant);

        $this->getJson('/_test/subscription/read')->assertOk();
        $this->postJson('/_test/subscription/write')
            ->assertStatus(402)
            ->assertJsonPath('message', 'subscription_payment_required');

        $this->assertTrue($subscription->refresh()->is_locked);
    }

    public function test_tenant_feature_override_takes_precedence_over_plan_features(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $plan = Plan::query()->create([
            'name' => 'Pro',
            'monthly_price' => 29,
            'yearly_price' => 290,
            'features' => ['advanced_reports' => false],
            'max_users' => 5,
            'max_products' => 500,
            'is_active' => true,
        ]);
        Subscription::query()->create([
            'user_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertFalse($tenant->hasFeature('advanced_reports'));

        TenantFeatureFlag::query()->create([
            'user_id' => $tenant->getKey(),
            'feature_key' => 'advanced_reports',
            'is_enabled' => true,
        ]);

        $this->assertTrue($tenant->hasFeature('advanced_reports'));
    }

    public function test_stripe_webhook_is_persisted_before_processing(): void
    {
        $this->postJson('/api/webhooks/stripe', [
            'id' => 'evt_unknown',
            'type' => 'test.unknown',
            'data' => ['object' => ['id' => 'object_1']],
        ])->assertOk()->assertJsonPath('status', 'ignored');

        $this->assertDatabaseHas(WebhookLog::class, [
            'provider' => 'stripe',
            'event_type' => 'test.unknown',
            'status' => 'success',
            'attempts' => 1,
        ]);
    }

    public function test_admin_role_policies_enforce_finance_and_support_boundaries(): void
    {
        $superAdmin = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_STAFF]);
        $financeAdmin = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_STAFF]);
        $supportAdmin = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_STAFF]);
        $tenant = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_TENANT]);

        $financeAdmin->givePermissionTo(Permission::findOrCreate('ViewAny:PaymentResource', 'web'));
        $supportAdmin->givePermissionTo([
            Permission::findOrCreate('ViewAny:SupportTicketResource', 'web'),
            Permission::findOrCreate('View:UserResource', 'web'),
        ]);
        $superAdmin->givePermissionTo(Permission::findOrCreate(RbacPermission::IMPERSONATE_TENANT, 'web'));

        $this->assertTrue((new PaymentPolicy)->viewAny($financeAdmin));
        $this->assertFalse((new PaymentPolicy)->viewAny($supportAdmin));
        $this->assertTrue((new SupportTicketPolicy)->viewAny($supportAdmin));
        $this->assertFalse((new SupportTicketPolicy)->viewAny($financeAdmin));
        $this->assertTrue((new UserPolicy)->view($supportAdmin, $tenant));
        $this->assertFalse((new UserPolicy)->update($supportAdmin, $tenant));
        $this->assertTrue((new UserPolicy)->impersonate($superAdmin, $tenant));
    }

    public function test_only_staff_with_panel_permission_can_access_filament(): void
    {
        $permission = Permission::findOrCreate(RbacPermission::ACCESS_ADMIN_PANEL, 'web');
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_STAFF]);
        $tenant = User::factory()->create(['account_type' => User::ACCOUNT_TYPE_TENANT]);

        $staff->givePermissionTo($permission);
        $tenant->givePermissionTo($permission);

        $panel = Filament::getPanel('admin');

        $this->assertTrue($staff->canAccessPanel($panel));
        $this->assertFalse($tenant->canAccessPanel($panel));
        $this->assertCount(1, User::query()->staff()->get());
        $this->assertCount(1, User::query()->tenants()->get());
    }

    public function test_dunning_command_initializes_grace_period_and_queues_day_one_warning(): void
    {
        Mail::fake();
        $tenant = User::factory()->create(['role' => 'tenant']);
        $plan = Plan::query()->create([
            'name' => 'Pro',
            'monthly_price' => 29,
            'yearly_price' => 290,
            'max_users' => 5,
            'max_products' => 500,
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'user_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDay(),
            'payment_failed_at' => now(),
        ]);

        $this->artisan('saas:dunning:process')->assertSuccessful();

        $subscription->refresh();
        $this->assertSame(1, $subscription->dunning_last_notified_day);
        $this->assertTrue($subscription->grace_period_ends_at?->isFuture());
        Mail::assertQueued(DunningWarningMail::class, fn (DunningWarningMail $mail): bool => $mail->day === 1);
    }

    public function test_dunning_command_warns_non_renewing_subscription_before_expiry(): void
    {
        Mail::fake();
        $tenant = User::factory()->create(['role' => 'tenant']);
        $plan = Plan::query()->create([
            'name' => 'Pro',
            'monthly_price' => 29,
            'yearly_price' => 290,
            'max_users' => 5,
            'max_products' => 500,
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'user_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDays(6),
            'auto_renews' => false,
        ]);

        $this->artisan('saas:dunning:process')->assertSuccessful();

        $subscription->refresh();
        $this->assertSame(6, $subscription->dunning_last_notified_day);
        $this->assertNull($subscription->grace_period_ends_at);
        Mail::assertQueued(
            DunningWarningMail::class,
            fn (DunningWarningMail $mail): bool => $mail->day === 6 && $mail->isImpendingExpiration,
        );
    }

    public function test_tenant_purge_never_touches_global_configuration(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        TenantFeatureFlag::query()->create([
            'user_id' => $tenant->getKey(),
            'feature_key' => 'beta_feature',
            'is_enabled' => true,
        ]);
        AppConfig::query()->create(['key' => 'global_setting', 'value' => 'preserved']);

        (new PurgeTenantDataJob((int) $tenant->getKey()))->handle();

        $this->assertDatabaseMissing('users', ['id' => $tenant->getKey()]);
        $this->assertDatabaseMissing('tenant_feature_flags', ['user_id' => $tenant->getKey()]);
        $this->assertDatabaseHas('app_configs', ['key' => 'global_setting', 'value' => 'preserved']);
    }
}
