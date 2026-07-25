<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources\StaffResource\Pages;

use App\Models\User;
use App\Saas\Filament\Resources\StaffResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['account_type'] = User::ACCOUNT_TYPE_STAFF;
        $data['role'] = 'staff';

        return $data;
    }
}
