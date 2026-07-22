<?php

namespace App\Saas\Filament\Resources\InstructionResource\Pages;

use App\Saas\Filament\Resources\InstructionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInstructions extends ManageRecords
{
    protected static string $resource = InstructionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
