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
    
    public static function getNavigationIcon(): ?string { 
        return 'heroicon-o-megaphone'; 
    }
    
    public static function getNavigationGroup(): ?string { 
        return __('SaaS Management'); 
    }

    public static function getNavigationLabel(): string { 
        return __('Announcements'); 
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label(__('Title'))->required(),
            Textarea::make('message')->label(__('Message'))->required(),
            Select::make('type')->label(__('Type'))->options([
                'info' => 'Info',
                'warning' => 'Warning',
                'danger' => 'Danger'
            ])->default('info')->required(),
            DateTimePicker::make('expires_at')->label(__('Expiry Date')),
            Toggle::make('is_active')->label(__('Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('Title'))->searchable(),
                TextColumn::make('type')->label(__('Type'))->badge(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(), 
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ManageAnnouncements::route('/'),
        ];
    }
}