<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources;

use App\Models\Instruction;
use App\Models\User;
use App\Saas\Filament\Resources\InstructionResource\Pages\ManageInstructions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstructionResource extends Resource
{
    protected static ?string $model = Instruction::class;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->hasAnyRole(['super_admin', 'support_admin']);
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Support & Help');
    }

    public static function getNavigationLabel(): string
    {
        return __('Instructions');
    }

    public static function getModelLabel(): string
    {
        return __('Instruction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Instructions');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label(__('Title'))->required(),
            RichEditor::make('content')->label(__('Content'))->required()->columnSpanFull(),
            TextInput::make('sort_order')->label(__('Sort Order'))->numeric()->default(0),
            Toggle::make('is_active')->label(__('Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('title')->label(__('Title'))->searchable(),
                TextColumn::make('sort_order')->label(__('Sort Order'))->sortable(),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInstructions::route('/'),
        ];
    }
}
