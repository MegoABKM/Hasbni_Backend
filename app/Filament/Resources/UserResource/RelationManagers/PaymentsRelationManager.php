<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'transaction_id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Payments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')
                ->label(__('Amount'))
                ->numeric()
                ->prefix('$')
                ->required(),
            TextInput::make('currency')
                ->label(__('Currency'))
                ->default('USD')
                ->required()
                ->maxLength(3),
            Select::make('payment_method')
                ->label(__('Payment Method'))
                ->options([
                    'manual' => __('Manual'),
                    'stripe' => __('Stripe'),
                    'paypal' => __('PayPal'),
                    'myfatoorah' => __('MyFatoorah'),
                ])
                ->default('manual')
                ->required(),
            Select::make('status')
                ->label(__('Status'))
                ->options([
                    'successful' => __('Successful'),
                    'failed' => __('Failed'),
                    'refunded' => __('Refunded'),
                ])
                ->default('successful')
                ->required(),
            TextInput::make('transaction_id')
                ->label(__('Transaction ID'))
                ->maxLength(255),
            DateTimePicker::make('paid_at')
                ->label(__('Paid At'))
                ->default(now())
                ->required(),
            Textarea::make('failure_reason')
                ->label(__('Failure Log'))
                ->disabled()
                ->columnSpanFull()
                ->visible(fn (callable $get): bool => $get('status') === 'failed'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money(fn ($record): string => $record->currency ?? 'USD')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->formatStateUsing(fn (string $state): string => __(match ($state) {
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'myfatoorah' => 'MyFatoorah',
                        default => 'Manual',
                    }))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => __(ucfirst($state)))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'successful' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('failure_reason')
                    ->label(__('Error Log'))
                    ->limit(30)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transaction_id')
                    ->label(__('Transaction ID'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label(__('Paid At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('Add Payment')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
