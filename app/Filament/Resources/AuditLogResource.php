<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Audit Logs');
    }

    public static function getModelLabel(): string
    {
        return __('Audit Log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Audit Logs');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('user.name')->label(__('User'))->disabled(),
            TextInput::make('event')->label(__('Action'))->disabled(),
            TextInput::make('auditable_type')->label(__('Target Model'))->disabled(),
            TextInput::make('ip_address')->label(__('IP Address'))->disabled(),
            Textarea::make('old_values')->label(__('Old Values'))->disabled()->columnSpanFull(),
            Textarea::make('new_values')->label(__('New Values'))->disabled()->columnSpanFull(),
            Textarea::make('user_agent')->label(__('Device Information'))->disabled()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime()->sortable(),
                TextColumn::make('user.name')->label(__('User'))->searchable()->sortable(),
                TextColumn::make('event')
                    ->label(__('Action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        default => 'primary',
                    })
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label(__('Target Model'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label(__('IP Address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
