<?php

namespace Tests\Feature;

use App\Models\User;
use App\Saas\Filament\Widgets\SaaSCountryAnalyticsWidget;
use App\Saas\Models\Payment;
use App\Saas\Models\Plan;
use App\Saas\Models\Subscription;
use App\Saas\Services\KpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KpiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_records_and_reads_daily_universal_saas_metrics(): void
    {
        Carbon::setTestNow('2026-07-22 12:00:00');

        $tenant = User::factory()->create([
            'role' => 'tenant',
            'country' => 'Syria',
            'created_at' => '2026-07-21 09:00:00',
        ]);
        $plan = Plan::query()->create([
            'name' => 'Growth',
            'monthly_price' => 100,
            'yearly_price' => 960,
            'max_users' => 10,
            'is_active' => true,
        ]);
        $subscription = Subscription::query()->create([
            'user_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => '2026-07-21 09:00:00',
            'ends_at' => '2026-08-21 09:00:00',
        ]);
        Payment::query()->create([
            'user_id' => $tenant->getKey(),
            'subscription_id' => $subscription->getKey(),
            'amount' => 100,
            'currency' => 'USD',
            'payment_method' => 'manual',
            'status' => 'successful',
            'paid_at' => '2026-07-21 09:00:00',
        ]);

        $service = app(KpiService::class);

        $this->assertSame(2, $service->recordDailyMetrics('2026-07-21'));
        $this->assertDatabaseHas('saas_daily_metrics', [
            'date' => '2026-07-21',
            'country' => 'Syria',
            'mrr' => 100,
            'active_subscriptions' => 1,
            'new_signups' => 1,
            'churned_subscriptions' => 0,
        ]);

        $summary = $service->summary([
            'period' => 'custom',
            'start_date' => '2026-07-21',
            'end_date' => '2026-07-21',
            'country' => 'Syria',
        ]);

        $this->assertSame(100.0, $summary['mrr']);
        $this->assertSame(1200.0, $summary['arr']);
        $this->assertSame(1, $summary['active_subscriptions']);
        $this->assertSame(100.0, $summary['platform_revenue']);
        $this->assertSame(1, $summary['new_signups']);

        $this->assertDatabaseHas('saas_daily_metrics', [
            'date' => '2026-07-21',
            'country' => KpiService::ALL_COUNTRIES,
            'new_mrr' => 100,
            'expansion_mrr' => 0,
            'contraction_mrr' => 0,
            'churned_mrr' => 0,
        ]);

        $last90Days = $service->resolveDateRange(['period' => 'last_90_days']);
        $this->assertSame('2026-04-24', $last90Days['start']->toDateString());
        $this->assertSame('2026-07-22', $last90Days['end']->toDateString());

        $lastSixMonths = $service->resolveDateRange(['period' => 'last_6_months']);
        $this->assertSame('2026-01-22', $lastSixMonths['start']->toDateString());

        $yearToDate = $service->resolveDateRange(['period' => 'this_year']);
        $this->assertSame('2026-01-01', $yearToDate['start']->toDateString());

        $allTime = $service->resolveDateRange(['period' => 'all_time']);
        $this->assertSame('2026-07-21', $allTime['start']->toDateString());
        $this->assertSame('2026-07-22', $allTime['end']->toDateString());
    }

    public function test_country_widget_aggregates_tenants_subscriptions_and_mrr(): void
    {
        Carbon::setTestNow('2026-07-22 12:00:00');

        $tenant = User::factory()->create([
            'role' => 'tenant',
            'country' => 'Syria',
        ]);
        User::factory()->create([
            'role' => 'tenant',
            'country' => 'Syria',
        ]);
        $plan = Plan::query()->create([
            'name' => 'Growth',
            'monthly_price' => 100,
            'yearly_price' => 960,
            'max_users' => 10,
            'is_active' => true,
        ]);
        Subscription::query()->create([
            'user_id' => $tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => '2026-07-01 00:00:00',
            'ends_at' => '2027-07-01 00:00:00',
        ]);

        $widget = new SaaSCountryAnalyticsWidget;
        $method = (new \ReflectionClass($widget))->getMethod('countryMetrics');
        $countries = collect($method->invoke($widget))->keyBy('country');

        $this->assertSame(2, $countries['Syria']['registered_users']);
        $this->assertSame(1, $countries['Syria']['active_subscriptions']);
        $this->assertSame(80.0, $countries['Syria']['mrr']);
    }

    public function test_it_records_new_expansion_contraction_and_churned_mrr(): void
    {
        Carbon::setTestNow('2026-07-21 12:00:00');

        $plans = collect([
            ['name' => 'Base', 'monthly_price' => 100],
            ['name' => 'Expansion', 'monthly_price' => 150],
            ['name' => 'Contraction', 'monthly_price' => 50],
        ])->mapWithKeys(function (array $attributes): array {
            $plan = Plan::query()->create([
                ...$attributes,
                'yearly_price' => $attributes['monthly_price'] * 10,
                'max_users' => 10,
                'is_active' => true,
            ]);

            return [$plan->name => $plan];
        });
        $users = collect();

        foreach (range(1, 3) as $index) {
            $users->push(User::factory()->create([
                'role' => 'tenant',
                'country' => 'Syria',
                'created_at' => '2026-07-19 09:00:00',
            ]));
        }

        $subscriptions = $users->map(fn (User $user): Subscription => Subscription::query()->create([
            'user_id' => $user->getKey(),
            'plan_id' => $plans['Base']->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => '2026-07-19 09:00:00',
            'ends_at' => '2026-08-19 09:00:00',
        ]));

        $this->assertSame(2, app(KpiService::class)->recordDailyMetrics('2026-07-20'));

        $subscriptions[0]->update([
            'status' => 'expired',
            'ends_at' => '2026-07-21 00:00:00',
        ]);
        Subscription::query()->create([
            'user_id' => $users[0]->getKey(),
            'plan_id' => $plans['Expansion']->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => '2026-07-21 00:00:00',
            'ends_at' => '2026-08-21 00:00:00',
        ]);
        $subscriptions[1]->update([
            'status' => 'expired',
            'ends_at' => '2026-07-21 00:00:00',
        ]);
        Subscription::query()->create([
            'user_id' => $users[1]->getKey(),
            'plan_id' => $plans['Contraction']->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => '2026-07-21 00:00:00',
            'ends_at' => '2026-08-21 00:00:00',
        ]);
        $subscriptions[2]->update([
            'status' => 'expired',
            'ends_at' => '2026-07-21 12:00:00',
        ]);

        User::factory()->create([
            'role' => 'tenant',
            'country' => 'Syria',
            'created_at' => '2026-07-21 09:00:00',
        ]);
        $newTenant = User::query()->latest('id')->firstOrFail();
        Subscription::query()->create([
            'user_id' => $newTenant->getKey(),
            'plan_id' => $plans['Base']->getKey(),
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => '2026-07-21 09:00:00',
            'ends_at' => '2026-08-21 09:00:00',
        ]);

        app(KpiService::class)->recordDailyMetrics('2026-07-21');

        $this->assertDatabaseHas('saas_daily_metrics', [
            'date' => '2026-07-21',
            'country' => KpiService::ALL_COUNTRIES,
            'new_mrr' => 100,
            'expansion_mrr' => 50,
            'contraction_mrr' => 50,
            'churned_mrr' => 100,
        ]);
    }
}
