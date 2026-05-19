<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
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
                // 🚀 عرض الاسم مع الإيميل في القائمة المنسدلة عند الإنشاء/التعديل
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('user_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // مخفي افتراضياً لتوفير المساحة
                    
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->description(fn (Payment $record): string => $record->user->email ?? 'No Email') // 🚀 يضع الإيميل تحت الاسم بخط رمادي صغير
                    ->searchable(['name', 'email']) // 🚀 يمكن البحث في الجدول عن طريق الاسم أو الإيميل
                    ->sortable(),

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
                SelectFilter::make('payment_method')->options([
                    'stripe' => 'Stripe', 'myfatoorah' => 'MyFatoorah', 'manual' => 'Manual/Cash',
                ]),
                Filter::make('failed_payments')->label('⚠️ Failed Payments')->toggle()->query(fn (Builder $query): Builder => $query->where('status', 'failed')),
                Filter::make('this_month')->label('📅 This Month Only')->toggle()->query(fn (Builder $query): Builder => $query->where('paid_at', '>=', Carbon::now()->startOfMonth())),
            ])
            ->recordActions([ 
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