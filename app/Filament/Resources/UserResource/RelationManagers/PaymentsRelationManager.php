<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema; 
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\CreateAction; 
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action; // 🚀 تم التصحيح هنا
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

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
                ->options(['manual' => 'Manual', 'stripe' => 'Stripe', 'paypal' => 'PayPal', 'myfatoorah' => 'MyFatoorah'])
                ->default('manual')
                ->required(),
            Select::make('status')
                ->options(['successful' => 'Successful', 'failed' => 'Failed', 'refunded' => 'Refunded'])
                ->default('successful')
                ->required(),
            TextInput::make('transaction_id')->label('Transaction ID'),
            DateTimePicker::make('paid_at')->default(now())->required(),
            
            Textarea::make('failure_reason')
                ->label('Failure Log (Gateway Error)')
                ->disabled()
                ->columnSpanFull()
                ->visible(fn ($get) => $get('status') === 'failed'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
            ->defaultSort('paid_at', 'desc')
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
                
                TextColumn::make('failure_reason')
                    ->label('Error Log')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen((string)$state) > 30 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add Payment'),
            ])
            ->actions([
                // 🚀 زر الاسترداد
                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Process Financial Refund')
                    ->modalDescription('Are you sure? This will refund the money to the customer via the payment gateway and mark the payment as Refunded.')
                    ->visible(fn (Payment $record) => $record->status === 'successful')
                    ->action(function (Payment $record) {
                        try {
                            if ($record->payment_method === 'myfatoorah') {
                                $response = Http::withToken(env('MYFATOORAH_TOKEN'))->post(env('MYFATOORAH_URL', 'https://apitest.myfatoorah.com') . '/v2/MakeRefund', [
                                    'KeyType' => 'InvoiceId',
                                    'Key' => $record->transaction_id,
                                    'RefundChargeOnCustomer' => false,
                                    'ServiceChargeOnCustomer' => false,
                                    'Amount' => $record->amount,
                                    'Comment' => 'Requested via Admin Panel'
                                ]);
                                
                                if (!$response->successful() || !$response->json('IsSuccess')) {
                                    throw new \Exception($response->json('Message') ?? 'MyFatoorah Refund Failed');
                                }
                            } 
                            elseif ($record->payment_method === 'stripe') {
                                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                                \Stripe\Refund::create(['payment_intent' => $record->transaction_id]);
                            }

                            $record->update(['status' => 'refunded']);
                            
                            if ($record->subscription_id) {
                                \App\Models\Subscription::where('id', $record->subscription_id)->update(['status' => 'canceled']);
                            }

                            Notification::make()->title('Refund processed successfully!')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Refund Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}