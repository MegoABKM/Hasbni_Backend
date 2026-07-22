<?php

namespace App\Saas\Filament\Resources;

use App\Saas\Filament\Resources\AppConfigResource\Pages;
use App\Saas\Models\AppConfig;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppConfigResource extends Resource
{
    protected static ?string $model = AppConfig::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Application Configuration');
    }

    public static function getModelLabel(): string
    {
        return __('Configuration');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Configurations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label(__('Key'))->required(),
            TextInput::make('value')->label(__('Value'))->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label(__('Key'))->searchable(),
                TextColumn::make('value')->label(__('Value')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAppConfigs::route('/'),
        ];
    }
}
