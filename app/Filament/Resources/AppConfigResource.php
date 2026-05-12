<?php

namespace App\Filament\Resources;

use App\Models\AppConfig;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\AppConfigResource\Pages;

// 🚀 التعديل هنا: استدعاء مسار الـ Actions الموحد الجديد 🚀
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class AppConfigResource extends Resource
{
    protected static ?string $model = AppConfig::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System Settings';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('key')->required(),
            TextInput::make('value')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable(),
                TextColumn::make('value')->searchable(),
            ])
            ->actions([
                // 🚀 التعديل هنا: استخدام الـ Actions من المسار الموحد 🚀
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        // 🚀 ربط المورد بالصفحة المخصصة له 🚀
        return [
            'index' => Pages\ManageAppConfigs::route('/'),
        ];
    }
}