<?php
namespace App\Filament\Resources;

use App\Models\Instruction;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class InstructionResource extends Resource
{
    protected static ?string $model = Instruction::class;
    
    public static function getNavigationIcon(): string { return 'heroicon-o-book-open'; }
    public static function getNavigationGroup(): ?string { return __('Support & Help'); }
    public static function getNavigationLabel(): string { return __('Instructions'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Title (العنوان)')->required(),
            RichEditor::make('content')->label('Content (المحتوى)')->required()->columnSpanFull(),
            TextInput::make('sort_order')->label('Sort Order (الترتيب)')->numeric()->default(0),
            Toggle::make('is_active')->label('Active (مفعل)')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([ EditAction::make(), DeleteAction::make() ]);
    }

    public static function getPages(): array {
        return ['index' => \App\Filament\Resources\InstructionResource\Pages\ManageInstructions::route('/')];
    }
}