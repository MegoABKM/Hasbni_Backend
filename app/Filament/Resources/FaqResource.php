<?php
namespace App\Filament\Resources;

use App\Models\Faq;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;
    public static function getNavigationIcon(): string { return 'heroicon-o-question-mark-circle'; }
  public static function getNavigationGroup(): ?string { return __('Support & Help'); }
    public static function getNavigationLabel(): string { return __('FAQs'); }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')->label('Question (السؤال)')->required(),
            Textarea::make('answer')->label('Answer (الإجابة)')->required(),
            TextInput::make('sort_order')->label('Sort Order (الترتيب)')->numeric()->default(0),
            Toggle::make('is_active')->label('Active (مفعل)')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('question')->searchable()->limit(50),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([ EditAction::make(), DeleteAction::make() ]);
    }

    public static function getPages(): array {
        return ['index' => \App\Filament\Resources\FaqResource\Pages\ManageFaqs::route('/')];
    }
}