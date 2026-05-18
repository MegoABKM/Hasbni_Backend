<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction; // 🚀 الأكشنز الموحدة لنسختك

class AuditLogsRelationManager extends RelationManager
{
    // 🚀 ربطها بالعلاقة التي أضفناها في موديل User
    protected static string $relationship = 'auditLogs';
    protected static ?string $recordTitleAttribute = 'event';
    protected static ?string $title = 'User Activity & Audit Trail';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('event')->label('Action')->disabled(),
            TextInput::make('auditable_type')->label('Target Model')->disabled(),
            TextInput::make('ip_address')->label('IP Address')->disabled(),
            Textarea::make('old_values')->label('Old Values (JSON)')->disabled()->columnSpanFull(),
            Textarea::make('new_values')->label('New Values (JSON)')->disabled()->columnSpanFull(),
            Textarea::make('user_agent')->label('Device Info')->disabled()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Time')->dateTime()->sortable(),
                TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login'   => 'info',
                        'logout'  => 'gray',
                        default   => 'primary',
                    }),
                TextColumn::make('auditable_type')
                    ->label('Target')
                    ->formatStateUsing(fn (string $state) => class_basename($state)),
                TextColumn::make('ip_address'),
            ])
            ->headerActions([]) // لا يمكن إنشاء سجل يدوياً
            ->actions([
                ViewAction::make(), // 👁️ للقراءة ورؤية JSON فقط
            ]);
    }
}