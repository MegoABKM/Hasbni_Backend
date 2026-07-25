<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources\StaffResource\Pages;

use App\Models\User;
use App\Saas\Filament\Resources\StaffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['account_type'] = User::ACCOUNT_TYPE_STAFF;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->record->isNot(auth()->user())),
        ];
    }
}
