<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
