<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Schemas\Schema; // 🚀 استخدام نسختك الحديثة
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction; // 🚀 الأكشن الموحد
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System Settings';
    }

    // 🔒 منع الإضافة (السجلات تُنشأ برمجياً فقط)
    public static function canCreate(): bool
    {
        return false;
    }

    // 🔒 منع التعديل
    public static function canEdit($record): bool
    {
        return false;
    }

    // 🔒 منع الحذف اليدوي (لأنك فعلت Prunable ليحذف القديم تلقائياً)
    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('user.name')->label('User')->disabled(),
            TextInput::make('event')->label('Action')->disabled(),
            TextInput::make('auditable_type')->label('Model')->disabled(),
            TextInput::make('ip_address')->label('IP Address')->disabled(),
            
            Textarea::make('old_values')
                ->label('Old Values (JSON)')
                ->disabled()
                ->columnSpanFull(),
                
            Textarea::make('new_values')
                ->label('New Values (JSON)')
                ->disabled()
                ->columnSpanFull(),
                
            Textarea::make('user_agent')
                ->label('Device Info')
                ->disabled()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                    
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                    
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
                    })
                    ->searchable(),
                    
                TextColumn::make('auditable_type')
                    ->label('Target Model')
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->searchable(),
                    
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(), // 👁️ زر العرض فقط
            ]);
    }

    public static function getPages(): array
    {
        return [
            // نحتاج صفحة Index فقط لأننا منعنا الإنشاء والتعديل
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}