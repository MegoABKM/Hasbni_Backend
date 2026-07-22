<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Plans');
    }

    public static function getModelLabel(): string
    {
        return __('Plan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Plans');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Plan Name'))->required(),
            TextInput::make('monthly_price')->label(__('Monthly Price'))->numeric()->prefix('$')->required(),
            TextInput::make('yearly_price')->label(__('Yearly Price'))->numeric()->prefix('$')->required(),
            TextInput::make('discount_percentage')->label(__('Discount Percentage'))->numeric()->suffix('%')->default(0),
            TextInput::make('max_users')->label(__('Maximum Users'))->numeric()->minValue(1)->required(),
            Toggle::make('is_active')->label(__('Active'))->default(true),
            KeyValue::make('features')
                ->label(__('Features'))
                ->keyLabel(__('Feature Name'))
                ->valueLabel(__('Value'))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Plan Name'))->searchable()->sortable(),
                TextColumn::make('monthly_price')->label(__('Monthly Price'))->money('USD')->sortable(),
                TextColumn::make('yearly_price')->label(__('Yearly Price'))->money('USD')->sortable(),
                TextColumn::make('max_users')->label(__('Maximum Users'))->numeric()->sortable(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
