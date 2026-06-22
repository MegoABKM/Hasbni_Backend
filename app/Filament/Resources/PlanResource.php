<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-rectangle-stack'; }
    public static function getNavigationGroup(): ?string { return __('SaaS Management'); }
    public static function getNavigationLabel(): string { return __('Plans'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Plan Name'))->required(),
            TextInput::make('monthly_price')->label(__('Monthly Price'))->numeric()->prefix('$')->required(),
            TextInput::make('yearly_price')->label(__('Yearly Price'))->numeric()->prefix('$')->required(),
            TextInput::make('discount_percentage')->label(__('Discount Percentage'))->numeric()->suffix('%')->default(0),
            TextInput::make('max_users')->label(__('Max Users'))->numeric()->required(),
            TextInput::make('max_products')->label(__('Max Products'))->numeric()->required(),
            Toggle::make('is_active')->label(__('Active'))->default(true),
            KeyValue::make('features')
                ->label(__('Features'))
                ->keyLabel(__('Feature Name'))
                ->valueLabel(__('Value')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('Plan Name'))->searchable()->sortable(),
                TextColumn::make('monthly_price')->label(__('Monthly Price'))->money('usd')->sortable(),
                TextColumn::make('yearly_price')->label(__('Yearly Price'))->money('usd')->sortable(),
                TextColumn::make('max_users')->label(__('Max Users'))->sortable(),
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