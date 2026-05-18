<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

// 🚀 تم توحيد مسارات الأزرار 🚀
use Filament\Actions\ViewAction;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public function form(Schema $schema): Schema
    {
        // عرض تفاصيل الفاتورة عند النقر عليها
        return $schema->schema([
            TextInput::make('invoice_number')->disabled(),
            TextInput::make('total_price')->disabled(),
            TextInput::make('total_profit')->disabled(),
            TextInput::make('currency_code')->disabled(),
            TextInput::make('payment_status')->disabled(),
            TextInput::make('created_at')->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')->searchable(),
                TextColumn::make('total_price')->sortable(),
                TextColumn::make('currency_code'),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'voided' => 'gray',
                        default => 'primary',
                    }),
                IconColumn::make('has_returns')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([
                ViewAction::make(), // 👁️ الفواتير للعرض والمراجعة فقط
            ]);
    }
}