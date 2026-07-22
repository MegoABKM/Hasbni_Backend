<?php

namespace App\Saas\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Active Devices and Sessions');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_used_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Device Name'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-computer-desktop'),
                TextColumn::make('created_at')
                    ->label(__('Login Time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->label(__('Last Activity'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('Never Used')),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label(__('Revoke Access'))
                    ->icon('heroicon-o-power')
                    ->modalHeading(__('Revoke Device Access'))
                    ->modalDescription(__('The tenant will need to sign in again on this device.'))
                    ->successNotificationTitle(__('Device access revoked successfully.')),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label(__('Revoke Selected'))
                    ->icon('heroicon-o-power'),
            ]);
    }
}
