<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
// 🚀 التعديل الجذري: استخدام المسار الموحد للأكشنز 🚀
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Billing & Revenue';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),
                TextInput::make('amount')->numeric()->prefix('$')->required(),
                TextInput::make('currency')->default('USD')->required(),
                Select::make('payment_method')
                    ->options(['manual' => 'Manual (Cash/Transfer)', 'stripe' => 'Stripe', 'paypal' => 'PayPal'])
                    ->default('manual')
                    ->required(),
                Select::make('status')
                    ->options(['successful' => 'Successful', 'failed' => 'Failed', 'refunded' => 'Refunded'])
                    ->default('successful')
                    ->required(),
                TextInput::make('transaction_id')->label('Transaction ID (Optional)'),
                DateTimePicker::make('paid_at')->default(now())->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable(),
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
                Tables\Filters\SelectFilter::make('status')->options(['successful' => 'Successful', 'failed' => 'Failed', 'refunded' => 'Refunded']),
            ])
            ->actions([
                // 👈 الآن سيعمل بسلام
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}