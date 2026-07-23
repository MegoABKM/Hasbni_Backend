<?php

declare(strict_types=1);

namespace App\Saas\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenantFeatureFlag extends Model
{
    protected $fillable = ['user_id', 'feature_key', 'is_enabled'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
