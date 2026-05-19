<?php
namespace App\Filament\Resources;

use App\Models\PromoCode;
use App\Filament\Resources\PromoCodeResource\Pages;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-ticket'; }
    public static function getNavigationGroup(): ?string { return 'SaaS Management'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->unique(ignoreRecord: true),
            TextInput::make('discount_percentage')->numeric()->required(),
            TextInput::make('max_uses')->numeric(),
            DateTimePicker::make('expires_at'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->badge(),
                TextColumn::make('discount_percentage')->suffix('%'),
                TextColumn::make('current_uses')->label('Used'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array {
        return ['index' => Pages\ManagePromoCodes::route('/')];
    }
}