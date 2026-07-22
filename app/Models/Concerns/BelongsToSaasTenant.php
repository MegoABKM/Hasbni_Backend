<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSaasTenant
{
    public function getTenantForeignKeyName(): string
    {
        return property_exists($this, 'tenantForeignKey')
            ? $this->tenantForeignKey
            : 'user_id';
    }

    public function getTenantOwnerKey(): int|string|null
    {
        return $this->getAttribute($this->getTenantForeignKeyName());
    }

    public function scopeForTenant(Builder $query, int|string $tenantKey): Builder
    {
        return $query->where($this->getTenantForeignKeyName(), $tenantKey);
    }
}
