<?php

namespace App\Filament\Resources\AppConfigResource\Pages;

use App\Filament\Resources\AppConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAppConfigs extends ManageRecords
{
    protected static string $resource = AppConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
