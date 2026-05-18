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
use Filament\Actions\EditAction; 
use Filament\Actions\DeleteAction; 

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;
    
    public static function getNavigationIcon(): string { 
        return 'heroicon-o-megaphone'; 
    }
    
    public static function getNavigationGroup(): ?string { 
        return 'SaaS Management'; 
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')->required()->columnSpanFull(),
            Textarea::make('message')->required()->columnSpanFull(),
            Select::make('type')->options([
                'info' => '🔵 Info (Normal)',
                'warning' => '🟠 Warning (Alert)',
                'danger' => '🔴 Danger (Urgent/Maintenance)'
            ])->default('info')->required(),
            DateTimePicker::make('expires_at')->label('Show Until (Expiry)'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('type')->badge()->color(fn (string $state): string => match ($state) {
                    'danger' => 'danger',
                    'warning' => 'warning',
                    default => 'info',
                }),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
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