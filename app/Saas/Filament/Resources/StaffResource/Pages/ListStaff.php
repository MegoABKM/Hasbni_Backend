<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources\StaffResource\Pages;

use App\Saas\Filament\Resources\StaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
