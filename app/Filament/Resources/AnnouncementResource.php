<?php
namespace App\Filament\Resources;

use App\Models\Announcement;
use App\Filament\Resources\AnnouncementResource\Pages;
use Filament\Schemas\Schema; 
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

// 🚀 مسار الأزرار الموحد والصحيح لنسختك
use Filament\Actions\EditAction; 
use Filament\Actions\DeleteAction; 

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;
    
    public static function getNavigationIcon(): ?string { 
        return 'heroicon-o-megaphone'; 
    }
    
    public static function getNavigationGroup(): ?string { 
        return 'SaaS Management'; 
    }

    public static function form(Schema $schema): Schema
    {
        // 🚀 استخدام components بدلاً من schema
        return $schema->components([
            TextInput::make('title')->required(),
            Textarea::make('message')->required(),
            Select::make('type')->options([
                'info' => 'Info',
                'warning' => 'Warning',
                'danger' => 'Danger'
            ])->default('info')->required(),
            DateTimePicker::make('expires_at')->label('Expiry Date'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('type')->badge(),
                IconColumn::make('is_active')->boolean(),
            ])
            // 🚀 التعديل السحري الذي سيحل مشكلة التحميل اللانهائي
            ->recordActions([
                EditAction::make(), 
                DeleteAction::make()
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ManageAnnouncements::route('/'),
        ];
    }
}