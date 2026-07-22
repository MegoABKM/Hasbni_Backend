<?php

namespace App\Saas\Filament\Resources\PromoCodeResource\Pages;

use App\Saas\Filament\Resources\PromoCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePromoCodes extends ManageRecords
{
    protected static string $resource = PromoCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
