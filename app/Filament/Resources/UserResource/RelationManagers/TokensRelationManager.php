<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

class TokensRelationManager extends RelationManager
{
    // العلاقة الافتراضية الخاصة بـ Laravel Sanctum
    protected static string $relationship = 'tokens';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $title = 'Active Devices & Sessions';
    
    // 🚀 الإصلاح هنا: تم تعديل نوع البيانات ليتطابق تماماً مع نظام PHP 8.3 و Filament v3
    protected static string|\BackedEnum|null $icon = 'heroicon-o-device-phone-mobile';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_used_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Device Name')
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-computer-desktop'),

                TextColumn::make('created_at')
                    ->label('Login Time')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never used'),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([
                // زر إلغاء صلاحية الجهاز
                DeleteAction::make()
                    ->label('Revoke Access')
                    ->icon('heroicon-o-power')
                    ->modalHeading('Revoke Device Access')
                    ->modalDescription('Are you sure you want to log this device out? The user will have to login again.')
                    ->successNotificationTitle('Device access revoked successfully.'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Revoke Selected')
                    ->icon('heroicon-o-power'),
            ]);
    }
}