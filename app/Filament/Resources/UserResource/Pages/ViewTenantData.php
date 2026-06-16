<?php
namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\UserResource\Widgets\TenantStatsWidget;
use App\Filament\Resources\UserResource\RelationManagers\SalesRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\CashTransactionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\AuditLogsRelationManager; // 🚀 استدعاء الكلاس الجديد

class ViewTenantData extends ViewRecord
{
    protected static string $resource = UserResource::class;
    
    protected ?string $heading = 'Tenant Dashboard & Data';

    protected function getHeaderWidgets(): array
    {
        return [
            TenantStatsWidget::class,
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            SalesRelationManager::class,
            CashTransactionsRelationManager::class,
            ProductsRelationManager::class,
            AuditLogsRelationManager::class, // 🚀 تمت الإضافة هنا!
        ];
    }
}