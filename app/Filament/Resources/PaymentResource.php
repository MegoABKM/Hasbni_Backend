<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action; // 🚀 تم التصحيح هنا
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    public static function getNavigationIcon(): ?string { return 'heroicon-o-banknotes'; }
    public static function getNavigationGroup(): ?string { return 'Billing & Revenue'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email}) - ID: {$record->id}")
                ->required()
                ->searchable(),
                
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
            TextInput::make('transaction_id')->label('Transaction ID (Optional)'),
            DateTimePicker::make('paid_at')->default(now())->required(),
            
            // الحقل الجديد لسجل الفشل
            Textarea::make('failure_reason')
                ->label('Failure Log (Gateway Error)')
                ->disabled()
                ->columnSpanFull()
                ->visible(fn ($get) => $get('status') === 'failed'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('user_id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')->label('Customer')->description(fn (Payment $record): string => $record->user->email ?? 'No Email')->searchable(['name', 'email'])->sortable(),
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
                
                // عرض الخطأ في الجدول (مخفي افتراضياً لتوفير المساحة)
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
            ->filters([
                SelectFilter::make('payment_method')->options(['stripe' => 'Stripe', 'myfatoorah' => 'MyFatoorah', 'manual' => 'Manual/Cash']),
                Filter::make('failed_payments')->label('⚠️ Failed Payments')->toggle()->query(fn (Builder $query): Builder => $query->where('status', 'failed')),
                Filter::make('this_month')->label('📅 This Month Only')->toggle()->query(fn (Builder $query): Builder => $query->where('paid_at', '>=', Carbon::now()->startOfMonth())),
            ])
            ->recordActions([
                // زر الاسترداد المالي (Refund)
                Action::make('refund')
                    ->label('Issue Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Process Financial Refund')
                    ->modalDescription('Are you sure? This will refund the money to the customer\'s card via the payment gateway and mark the payment as Refunded.')
                    ->visible(fn (Payment $record) => $record->status === 'successful') // يظهر للناجحة فقط
                    ->action(function (Payment $record) {
                        try {
                            if ($record->payment_method === 'myfatoorah') {
                                // كود استرداد MyFatoorah
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
                                // كود استرداد Stripe
                                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                                \Stripe\Refund::create([
                                  'payment_intent' => $record->transaction_id,
                                ]);
                            }

                            // تحديث حالة الدفعة
                            $record->update(['status' => 'refunded']);
                            
                            // إيقاف الاشتراك الخاص بهذه الدفعة
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

    public static function getPages(): array {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}