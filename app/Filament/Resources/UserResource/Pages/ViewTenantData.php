<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ProfileRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TokensRelationManager;
use App\Filament\Resources\UserResource\Widgets\TenantStatsWidget;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewTenantData extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string|Htmlable
    {
        return __('Tenant Details');
    }

    protected function getHeaderActions(): array
    {
        return [
            UserResource::impersonationAction(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TenantStatsWidget::class,
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            ProfileRelationManager::class,
            SubscriptionsRelationManager::class,
            PaymentsRelationManager::class,
            TokensRelationManager::class,
            AuditLogsRelationManager::class,
        ];
    }
}
