<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\DunningWarningMail;
use App\Saas\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class ProcessSubscriptionDunning extends Command
{
    protected $signature = 'saas:dunning:process';

    protected $description = 'Process subscription grace periods and queue dunning warnings';

    public function handle(): int
    {
        $processed = 0;

        Subscription::query()
            ->with('user')
            ->whereHas('user')
            ->where(function (Builder $query): void {
                $query
                    ->whereNotNull('payment_failed_at')
                    ->orWhere(function (Builder $query): void {
                        $query->where('ends_at', '>=', now()->subDays(7))
                            ->where('ends_at', '<=', now());
                    })
                    ->orWhere(function (Builder $query): void {
                        $query->where('status', 'active')
                            ->where('auto_renews', false)
                            ->whereBetween('ends_at', [now(), now()->addDays(7)]);
                    });
            })
            ->chunkById(200, function ($subscriptions) use (&$processed): void {
                foreach ($subscriptions as $subscription) {
                    $this->processSubscription($subscription);
                    $processed++;
                }
            });

        $this->info("Processed {$processed} subscriptions.");

        return self::SUCCESS;
    }

    private function processSubscription(Subscription $subscription): void
    {
        $isImpendingExpiration = $subscription->payment_failed_at === null
            && $subscription->ends_at?->isFuture() === true;
        $failureAt = $subscription->payment_failed_at ?? $subscription->ends_at ?? now();
        $day = $isImpendingExpiration
            ? max(1, (int) now()->startOfDay()->diffInDays($failureAt->copy()->startOfDay()))
            : max(1, (int) $failureAt->copy()->startOfDay()->diffInDays(now()->startOfDay()) + 1);
        $graceEndsAt = $subscription->grace_period_ends_at ?? $failureAt->copy()->addDays(7);
        $lastNotifiedDay = $subscription->dunning_last_notified_day;
        $shouldNotify = in_array($day, [1, 3, 6], true)
            && ($isImpendingExpiration
                ? $lastNotifiedDay === null || $lastNotifiedDay > $day
                : (int) $lastNotifiedDay < $day);

        DB::transaction(function () use (
            $subscription,
            $graceEndsAt,
            $day,
            $shouldNotify,
            $isImpendingExpiration,
        ): void {
            $values = [
                'dunning_last_notified_day' => $shouldNotify
                    ? $day
                    : $subscription->dunning_last_notified_day,
            ];

            if (! $isImpendingExpiration) {
                $values += [
                    'status' => $subscription->ends_at?->isPast() === true ? 'expired' : $subscription->status,
                    'grace_period_ends_at' => $graceEndsAt,
                    'is_locked' => $graceEndsAt->isPast(),
                ];
            }

            $subscription->forceFill($values)->save();
        });

        if ($shouldNotify && $subscription->user !== null) {
            Mail::to($subscription->user)->queue(
                new DunningWarningMail($subscription, $day, $isImpendingExpiration),
            );
        }
    }
}
