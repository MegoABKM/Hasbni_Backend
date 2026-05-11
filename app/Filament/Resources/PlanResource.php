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
use Filament\Actions\EditAction;
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
        return 'SaaS Management';
    }

    public static function form(Schema $schema): Schema
    {
        // تم استبدال Form بـ Schema ليتوافق مع إصدارك، وتمت إزالة Section لتجنب أخطاء المسارات
        return $schema
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('monthly_price')->numeric()->prefix('$')->required(),
                TextInput::make('yearly_price')->numeric()->prefix('$')->required(),
                TextInput::make('discount_percentage')->numeric()->suffix('%')->default(0),
                TextInput::make('max_users')->numeric()->required(),
                TextInput::make('max_products')->numeric()->required(),
                Toggle::make('is_active')->default(true),
                KeyValue::make('features')
                    ->keyLabel('Feature Name (e.g. can_sync)')
                    ->valueLabel('Value (e.g. true, false, 5)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('monthly_price')->money('usd')->sortable(),
                TextColumn::make('yearly_price')->money('usd')->sortable(),
                TextColumn::make('max_users')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
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