<?php

declare(strict_types=1);

namespace App\Saas\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'provider',
        'provider_purchase_token',
        'provider_purchase_token_hash',
        'provider_original_transaction_id',
        'auto_renews',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'grace_period_ends_at',
        'payment_failed_at',
        'dunning_last_notified_day',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'payment_failed_at' => 'datetime',
            'dunning_last_notified_day' => 'integer',
            'is_locked' => 'boolean',
            'auto_renews' => 'boolean',
            'provider_purchase_token' => 'encrypted',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeChurned(Builder $query): Builder
    {
        return $query->whereIn('status', ['expired', 'canceled']);
    }

    public function scopeActiveAt(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->active()
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $date);
            });
    }
}
