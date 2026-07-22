<?php

declare(strict_types=1);

namespace App\Saas\Filament\Exports;

use App\Saas\Models\Payment;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

final class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label(__('ID')),
            ExportColumn::make('user.name')
                ->label(__('Tenant'))
                ->formatStateUsing(fn (mixed $state): string => self::sanitize($state)),
            ExportColumn::make('user.email')
                ->label(__('Email'))
                ->formatStateUsing(fn (mixed $state): string => self::sanitize($state)),
            ExportColumn::make('subscription.plan.name')
                ->label(__('Plan'))
                ->formatStateUsing(fn (mixed $state): string => self::sanitize($state)),
            ExportColumn::make('amount')->label(__('Amount')),
            ExportColumn::make('currency')->label(__('Currency')),
            ExportColumn::make('payment_method')
                ->label(__('Payment Method'))
                ->formatStateUsing(fn (mixed $state): string => self::sanitize($state)),
            ExportColumn::make('status')
                ->label(__('Status'))
                ->formatStateUsing(fn (mixed $state): string => self::sanitize($state)),
            ExportColumn::make('transaction_id')
                ->label(__('Transaction ID'))
                ->formatStateUsing(fn (mixed $state): string => self::sanitize($state)),
            ExportColumn::make('paid_at')->label(__('Paid At')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('Payments export completed: :count records.', [
            'count' => number_format($export->successful_rows),
        ]);
    }

    public function getFormats(): array
    {
        return [ExportFormat::Csv];
    }

    private static function sanitize(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return in_array($value[0] ?? '', ['=', '+', '-', '@'], true)
            ? "'{$value}"
            : $value;
    }
}
