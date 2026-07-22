<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\KpiService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SaasTestDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private const DEMO_EMAIL_PREFIX = 'saas-demo-';

    private const DEMO_EMAIL_DOMAIN = '@example.test';

    public function run(): void
    {
        $now = now();
        $historyStart = $now->copy()->subDays(365)->startOfDay();

        DB::transaction(function () use ($now, $historyStart): void {
            $this->removePreviousDemoData();

            $plans = $this->createPlans();
            $countries = [
                'United States',
                'Saudi Arabia',
                'Egypt',
                'United Arab Emirates',
                'United Kingdom',
                'Canada',
                'Germany',
                'Jordan',
            ];
            $password = Hash::make('password');
            $paymentRows = [];

            for ($index = 1; $index <= 100; $index++) {
                $isFree = $index <= 40;
                $isActivePaid = ! $isFree && $index <= 88;
                $createdAt = $this->randomSignupDate(
                    $historyStart,
                    $isActivePaid || $isFree
                        ? $now
                        : $now->copy()->subDays(45),
                );
                $country = $countries[array_rand($countries)];

                $tenant = new User([
                    'name' => sprintf('SaaS Demo Tenant %03d', $index),
                    'email' => self::DEMO_EMAIL_PREFIX.$index.self::DEMO_EMAIL_DOMAIN,
                    'password' => $password,
                    'role' => 'tenant',
                    'is_banned' => false,
                    'phone' => '+1'.str_pad((string) $index, 10, '0', STR_PAD_LEFT),
                    'country' => $country,
                    'business_type' => $this->businessType($index),
                ]);
                $tenant->forceFill([
                    'email_verified_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();

                $plan = $isFree
                    ? $plans['Free']
                    : $plans[$index % 3 === 0 ? 'Enterprise' : 'Pro'];
                $billingCycle = $isFree || $index % 4 === 0 ? 'yearly' : 'monthly';
                [$status, $endsAt] = $this->subscriptionState($isFree, $isActivePaid, $createdAt, $now);

                $subscription = Subscription::query()->create([
                    'user_id' => $tenant->getKey(),
                    'plan_id' => $plan->getKey(),
                    'status' => $status,
                    'billing_cycle' => $isFree ? 'lifetime' : $billingCycle,
                    'starts_at' => $createdAt,
                    'ends_at' => $endsAt,
                ]);

                if (! $isFree) {
                    foreach ($this->paymentRows($subscription, $plan, $now) as $paymentRow) {
                        $paymentRows[] = $paymentRow;
                    }
                }
            }

            foreach (array_chunk($paymentRows, 500) as $chunk) {
                Payment::query()->insert($chunk);
            }
        });

        $kpis = app(KpiService::class);
        $date = $historyStart->copy();
        $today = $now->copy()->startOfDay();

        while ($date->lessThanOrEqualTo($today)) {
            $kpis->recordDailyMetrics($date->copy());
            $date->addDay();
        }

        $this->command?->info('SaaS demo data and 12 months of daily KPI snapshots were generated.');
    }

    /**
     * @return array<string, Plan>
     */
    private function createPlans(): array
    {
        $definitions = [
            'Free' => [
                'monthly_price' => 0,
                'yearly_price' => 0,
                'max_users' => 1,
                'features' => ['analytics' => 'basic', 'support' => 'community'],
            ],
            'Pro' => [
                'monthly_price' => 29,
                'yearly_price' => 290,
                'max_users' => 10,
                'features' => ['analytics' => 'advanced', 'support' => 'priority'],
            ],
            'Enterprise' => [
                'monthly_price' => 99,
                'yearly_price' => 990,
                'max_users' => 100,
                'features' => ['analytics' => 'advanced', 'support' => 'dedicated'],
            ],
        ];
        $plans = [];

        foreach ($definitions as $name => $attributes) {
            $plans[$name] = Plan::query()->updateOrCreate(
                ['name' => $name],
                [
                    ...$attributes,
                    'discount_percentage' => 0,
                    'is_active' => true,
                ],
            );
        }

        return $plans;
    }

    private function removePreviousDemoData(): void
    {
        $tenantIds = User::query()
            ->where('email', 'like', self::DEMO_EMAIL_PREFIX.'%'.self::DEMO_EMAIL_DOMAIN)
            ->pluck('id');

        if ($tenantIds->isEmpty()) {
            return;
        }

        Payment::query()->whereIn('user_id', $tenantIds)->delete();
        Subscription::query()->whereIn('user_id', $tenantIds)->delete();
        User::query()->whereIn('id', $tenantIds)->delete();
    }

    private function randomSignupDate(Carbon $start, Carbon $end): Carbon
    {
        return Carbon::createFromTimestamp(
            random_int($start->timestamp, $end->timestamp),
        );
    }

    /**
     * @return array{0: string, 1: Carbon|null}
     */
    private function subscriptionState(
        bool $isFree,
        bool $isActivePaid,
        Carbon $createdAt,
        Carbon $now,
    ): array {
        if ($isFree || $isActivePaid) {
            return ['active', $isFree ? null : $now->copy()->addDays(random_int(30, 365))];
        }

        $daysSinceSignup = max(30, $createdAt->diffInDays($now));
        $endsAt = $createdAt->copy()->addDays(random_int(14, $daysSinceSignup - 1));

        return [random_int(0, 1) === 0 ? 'canceled' : 'expired', $endsAt];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentRows(Subscription $subscription, Plan $plan, Carbon $now): array
    {
        $startsAt = Carbon::instance($subscription->starts_at);
        $endsAt = $subscription->ends_at
            ? Carbon::instance($subscription->ends_at)->min($now)
            : $now->copy();
        $amount = $subscription->billing_cycle === 'yearly'
            ? (float) $plan->yearly_price
            : (float) $plan->monthly_price;
        $rows = [];
        $paymentDate = $startsAt->copy();
        $sequence = 1;

        while ($paymentDate->lessThanOrEqualTo($endsAt)) {
            $paidAt = $paymentDate->copy()->setTime(9, 0);
            $rows[] = [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->getKey(),
                'amount' => $amount,
                'currency' => 'USD',
                'payment_method' => $sequence % 3 === 0 ? 'paypal' : 'stripe',
                'status' => 'successful',
                'transaction_id' => sprintf('saas-demo-%d-%d', $subscription->getKey(), $sequence),
                'failure_reason' => null,
                'paid_at' => $paidAt,
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ];

            $paymentDate = $subscription->billing_cycle === 'yearly'
                ? $paymentDate->addYearNoOverflow()
                : $paymentDate->addMonthNoOverflow();
            $sequence++;
        }

        return $rows;
    }

    private function businessType(int $index): string
    {
        return [
            'Analytics Platform',
            'Project Management',
            'Customer Support',
            'Marketing Automation',
            'Financial Operations',
        ][$index % 5];
    }
}
