<?php

namespace Tests\Feature;

use App\Filament\Widgets\SaaSCountryAnalyticsWidget;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\KpiService;
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
}
