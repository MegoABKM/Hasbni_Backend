<?php

namespace App\Saas\Filament\Resources;

use App\Saas\Filament\Resources\PromoCodeResource\Pages;
use App\Saas\Models\PromoCode;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-ticket';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Promo Codes');
    }

    public static function getModelLabel(): string
    {
        return __('Promo Code');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Promo Codes');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('Code'))->required()->unique(ignoreRecord: true),
            TextInput::make('discount_percentage')->label(__('Discount Percentage'))->numeric()->suffix('%')->required(),
            TextInput::make('max_uses')->label(__('Maximum Uses'))->numeric(),
            DateTimePicker::make('expires_at')->label(__('Expires At')),
            Toggle::make('is_active')->label(__('Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount(['payments as paid_uses_count' => fn (Builder $query): Builder => $query->where('status', 'successful')])
                ->withSum(['payments as generated_revenue' => fn (Builder $query): Builder => $query->where('status', 'successful')], 'amount'))
            ->columns([
                TextColumn::make('code')->label(__('Code'))->searchable()->badge()->color('primary'),
                TextColumn::make('discount_percentage')->label(__('Discount Percentage'))->suffix('%')->sortable(),
                TextColumn::make('paid_uses_count')->label(__('Paid Uses'))->numeric()->sortable(),
                TextColumn::make('generated_revenue')->label(__('Generated Revenue'))->money('USD')->sortable(),
                TextColumn::make('expires_at')->label(__('Expires At'))->dateTime()->sortable(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePromoCodes::route('/'),
        ];
    }
}
