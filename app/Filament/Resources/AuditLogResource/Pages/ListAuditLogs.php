<?php
namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        // لا يوجد أزرار هنا لأننا لا نريد من المدير إنشاء سجل يدوياً
        return [];
    }
}