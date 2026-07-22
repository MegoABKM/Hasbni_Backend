<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Revenue');
    }

    public static function getNavigationLabel(): string
    {
        return __('Payments');
    }

    public static function getModelLabel(): string
    {
        return __('Payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Payments');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->label(__('User'))
                ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} ({$record->email}) - ".__('ID').": {$record->id}")
                ->required()
                ->searchable(),

            TextInput::make('amount')
                ->label(__('Amount'))
                ->numeric()
                ->prefix('$')
                ->required(),

            TextInput::make('currency')
                ->label(__('Currency'))
                ->default('USD')
                ->required(),

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
                ->label(__('Transaction ID')),

            DateTimePicker::make('paid_at')
                ->label(__('Paid At'))
                ->default(now())
                ->required(),

            Textarea::make('failure_reason')
                ->label(__('Failure Log'))
                ->disabled()
                ->columnSpanFull()
                ->visible(fn ($get): bool => $get('status') === 'failed'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('user_id')
                    ->label(__('ID'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->description(fn (Payment $record): string => $record->user->email ?? __('No Email'))
                    ->searchable(['name', 'email'])
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('usd')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->formatStateUsing(fn (string $state): string => __($state))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __($state))
                    ->color(fn (string $state): string => match ($state) {
                        'successful' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('failure_reason')
                    ->label(__('Error Log'))
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paid_at')
                    ->label(__('Paid At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options([
                        'stripe' => __('Stripe'),
                        'myfatoorah' => __('MyFatoorah'),
                        'manual' => __('Manual'),
                    ]),

                Filter::make('failed_payments')
                    ->label(__('Failed Payments'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('status', 'failed')),

                Filter::make('this_month')
                    ->label(__('This Month Only'))
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('paid_at', '>=', Carbon::now()->startOfMonth())),
            ])
            ->recordActions([
                Action::make('refund')
                    ->label(__('Issue Refund'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('Process Financial Refund'))
                    ->modalDescription(__('Are you sure? This will refund the money to the customer via the payment gateway and mark the payment as Refunded.'))
                    ->visible(fn (Payment $record): bool => $record->status === 'successful')
                    ->action(function (Payment $record): void {
                        try {
                            if ($record->payment_method === 'myfatoorah') {
                                $response = Http::withToken(env('MYFATOORAH_TOKEN'))->post(env('MYFATOORAH_URL', 'https://apitest.myfatoorah.com').'/v2/MakeRefund', [
                                    'KeyType' => 'InvoiceId',
                                    'Key' => $record->transaction_id,
                                    'RefundChargeOnCustomer' => false,
                                    'ServiceChargeOnCustomer' => false,
                                    'Amount' => $record->amount,
                                    'Comment' => 'Requested via Admin Panel',
                                ]);

                                if ((! $response->successful()) || (! $response->json('IsSuccess'))) {
                                    throw new \Exception($response->json('Message') ?? 'MyFatoorah Refund Failed');
                                }
                            } elseif ($record->payment_method === 'stripe') {
                                if (! env('STRIPE_SECRET')) {
                                    throw new \Exception('Stripe is not configured.');
                                }

                                $response = Http::asForm()
                                    ->withToken(env('STRIPE_SECRET'))
                                    ->post('https://api.stripe.com/v1/refunds', [
                                        'payment_intent' => $record->transaction_id,
                                    ]);

                                if (! $response->successful()) {
                                    throw new \Exception($response->json('error.message') ?? 'Stripe refund failed.');
                                }
                            }

                            $record->update(['status' => 'refunded']);

                            if ($record->subscription_id) {
                                Subscription::where('id', $record->subscription_id)->update(['status' => 'canceled']);
                            }

                            Notification::make()
                                ->title(__('Refund processed successfully!'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Refund Failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

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
