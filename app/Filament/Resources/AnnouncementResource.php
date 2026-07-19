<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Announcements');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label(__('Title'))
                ->required()
                ->maxLength(255),

            Textarea::make('message')
                ->label(__('Message'))
                ->required()
                ->rows(5)
                ->columnSpanFull(),

            Select::make('type')
                ->label(__('Type'))
                ->options([
                    'info' => __('Info'),
                    'warning' => __('Warning'),
                    'danger' => __('Danger'),
                ])
                ->default('info')
                ->required()
                ->native(false),

            DateTimePicker::make('expires_at')
                ->label(__('Expiry Date'))
                ->seconds(false),

            Toggle::make('is_active')
                ->label(__('Active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('message')
                    ->label(__('Message'))
                    ->limit(70)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'warning' => 'warning',
                        'danger' => 'danger',
                        default => 'info',
                    })
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('expires_at')
                    ->label(__('Expiry Date'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('Never')),

                TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAnnouncements::route('/'),
        ];
    }
}
