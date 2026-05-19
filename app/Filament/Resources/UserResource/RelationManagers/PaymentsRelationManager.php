<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema; // 🚀 التوافق مع نسختك
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction; // 🚀 الأكشنز الموحدة
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $recordTitleAttribute = 'transaction_id';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('amount')->numeric()->prefix('$')->required(),
            TextInput::make('currency')->default('USD')->required(),
            Select::make('payment_method')
                ->options(['manual' => 'Manual', 'stripe' => 'Stripe', 'paypal' => 'PayPal'])
                ->default('manual')
                ->required(),
            Select::make('status')
                ->options(['successful' => 'Successful', 'failed' => 'Failed', 'refunded' => 'Refunded'])
                ->default('successful')
                ->required(),
            TextInput::make('transaction_id')->label('Transaction ID'),
            DateTimePicker::make('paid_at')->default(now())->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
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
            ->headerActions([
                CreateAction::make()->label('Add Payment'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}