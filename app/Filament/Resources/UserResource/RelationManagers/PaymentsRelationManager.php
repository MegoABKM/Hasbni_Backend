<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('currency')
                    ->default('USD')
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'manual' => 'Manual (Cash/Transfer)', 
                        'stripe' => 'Stripe', 
                        'paypal' => 'PayPal', 
                        'google_play' => 'Google Play'
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'successful' => 'Successful', 
                        'failed' => 'Failed', 
                        'refunded' => 'Refunded'
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('paid_at')->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('amount')->money('usd')->sortable(),
                TextColumn::make('payment_method')->badge()->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'successful' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // 🚀 استدعاء الأزرار مباشرة من كلاس Tables 🚀
                Tables\Actions\CreateAction::make()->label('Add Payment'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}