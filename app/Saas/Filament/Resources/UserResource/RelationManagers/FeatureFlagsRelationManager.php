<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources\UserResource\RelationManagers;

use App\Support\RbacPermission;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class FeatureFlagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tenantFeatureFlags';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Feature Flags');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can(RbacPermission::MANAGE_FEATURE_FLAGS) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('feature_key')
                ->label(__('Feature Key'))
                ->alphaDash()
                ->maxLength(100)
                ->required(),
            Toggle::make('is_enabled')
                ->label(__('Enabled'))
                ->default(true)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('feature_key')->label(__('Feature Key'))->searchable()->sortable(),
                IconColumn::make('is_enabled')->label(__('Enabled'))->boolean(),
                TextColumn::make('updated_at')->label(__('Updated At'))->since(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
