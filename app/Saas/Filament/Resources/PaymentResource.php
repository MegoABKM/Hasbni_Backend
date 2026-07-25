<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources;

use App\Models\User;
use App\Saas\Filament\Exports\PaymentExporter;
use App\Saas\Filament\Resources\PaymentResource\Pages;
use App\Saas\Models\Payment;
use App\Saas\Services\PaymentRefundService;
use App\Support\RbacPermission;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('ViewAny:PaymentResource');
    }

    public static function getNavigationIcon(): string
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
            Section::make(__('Payment Details'))
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        Select::make('user_id')
                            ->label(__('Tenant'))
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->tenants(),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn ($record): string => "{$record->name} ({$record->email})",
                            )
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->required(),
                        TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('currency')
                            ->label(__('Currency'))
                            ->default('USD')
                            ->maxLength(3)
                            ->required(),
                        Select::make('payment_method')
                            ->label(__('Payment Method'))
                            ->options(self::paymentMethodOptions())
                            ->default('manual')
                            ->required(),
                        Select::make('status')
                            ->label(__('Status'))
                            ->options(self::statusOptions())
                            ->default('successful')
                            ->required(),
                        TextInput::make('transaction_id')
                            ->label(__('Transaction ID'))
                            ->maxLength(255),
                        DateTimePicker::make('paid_at')
                            ->label(__('Paid At'))
                            ->default(now())
                            ->required(),
                    ]),
                    Textarea::make('failure_reason')
                        ->label(__('Failure Log'))
                        ->rows(4)
                        ->columnSpanFull()
                        ->visible(fn (callable $get): bool => $get('status') === 'failed'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label(__('Tenant'))
                    ->description(fn (Payment $record): string => $record->user?->email ?? __('No Email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money(fn (Payment $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->formatStateUsing(fn (string $state): string => self::paymentMethodOptions()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'successful' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('transaction_id')
                    ->label(__('Transaction ID'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('failure_reason')
                    ->label(__('Failure Log'))
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label(__('Paid At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options(self::paymentMethodOptions()),
                Filter::make('this_month')
                    ->label(__('This Month'))
                    ->query(fn (Builder $query): Builder => $query->whereBetween('paid_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ])),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->label(__('Export Payments'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exporter(PaymentExporter::class)
                    ->columnMappingColumns(2)
                    ->visible(fn (): bool => auth()->user()?->can(RbacPermission::EXPORT_PAYMENTS) ?? false),
            ])
            ->recordActions([
                Action::make('refund')
                    ->label(__('Refund Payment'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Payment $record): bool => $record->status === 'successful'
                        && in_array($record->payment_method, ['stripe', 'myfatoorah'], true)
                        && (auth()->user()?->can(RbacPermission::REFUND_PAYMENT) ?? false))
                    ->requiresConfirmation()
                    ->modalDescription(__('The refund will be submitted directly to the payment gateway and cannot be undone.'))
                    ->action(function (Payment $record): void {
                        app(PaymentRefundService::class)->refund($record);

                        Notification::make()
                            ->title(__('Payment refunded successfully'))
                            ->success()
                            ->send();
                    }),
                Action::make('print_receipt')
                    ->label(__('Print Receipt'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(
                        fn (Payment $record): string => route('payment.receipt', $record->getKey()),
                        shouldOpenInNewTab: true,
                    ),
                Action::make('download_invoice')
                    ->label(__('Download PDF Invoice'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(
                        fn (Payment $record): string => route('payment.invoice.download', $record),
                        shouldOpenInNewTab: true,
                    ),
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

    /**
     * @return array<string, string>
     */
    private static function paymentMethodOptions(): array
    {
        return [
            'manual' => __('Manual'),
            'stripe' => __('Stripe'),
            'paypal' => __('PayPal'),
            'myfatoorah' => __('MyFatoorah'),
            'bank_transfer' => __('Bank Transfer'),
            'other' => __('Other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'successful' => __('Successful'),
            'failed' => __('Failed'),
            'refunded' => __('Refunded'),
        ];
    }
}
