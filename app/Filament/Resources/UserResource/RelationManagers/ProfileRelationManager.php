<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProfileRelationManager extends RelationManager
{
    protected static string $relationship = 'profile';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Profile');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('organization_name')
                ->label(__('Organization Name'))
                ->required()
                ->maxLength(255),
            TextInput::make('phone_number')
                ->label(__('Phone Number'))
                ->tel()
                ->maxLength(50),
            TextInput::make('city')
                ->label(__('City'))
                ->maxLength(100),
            TextInput::make('address')
                ->label(__('Address'))
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization_name')
                    ->label(__('Organization Name'))
                    ->searchable(),
                TextColumn::make('phone_number')
                    ->label(__('Phone Number')),
                TextColumn::make('city')
                    ->label(__('City')),
                TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Create Profile'))
                    ->visible(fn (): bool => $this->getOwnerRecord()->profile()->doesntExist()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
