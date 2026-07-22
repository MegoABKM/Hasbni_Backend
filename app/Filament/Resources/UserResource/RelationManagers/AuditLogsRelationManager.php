<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $recordTitleAttribute = 'event';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Audit Logs');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('event')->label(__('Action'))->disabled(),
            TextInput::make('auditable_type')->label(__('Target Model'))->disabled(),
            TextInput::make('ip_address')->label(__('IP Address'))->disabled(),
            Textarea::make('old_values')->label(__('Old Values'))->disabled()->columnSpanFull(),
            Textarea::make('new_values')->label(__('New Values'))->disabled()->columnSpanFull(),
            Textarea::make('user_agent')->label(__('Device Information'))->disabled()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('event')
                    ->label(__('Action'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('auditable_type')
                    ->label(__('Target'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label(__('IP Address'))
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
