<?php
namespace App\Filament\Resources\InstructionResource\Pages;
use App\Filament\Resources\InstructionResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;

class ManageInstructions extends ManageRecords {
    protected static string $resource = InstructionResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}