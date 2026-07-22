<?php

namespace App\Contracts\Saas;

interface TenantOwnedRecord
{
    public function getTenantForeignKeyName(): string;

    public function getTenantOwnerKey(): int|string|null;
}
