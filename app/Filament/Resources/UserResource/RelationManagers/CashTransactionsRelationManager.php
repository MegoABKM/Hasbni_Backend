<?php
namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;

class CashTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'cashTransactions';
    protected static ?string $recordTitleAttribute = 'transaction_type';
    protected static ?string $title = 'Receipts & Cash Drawer';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('transaction_type')->disabled(),
            TextInput::make('amount')->disabled(),
            TextInput::make('currency_code')->disabled(),
            TextInput::make('created_at')->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('transaction_type')->badge()->searchable(),
                TextColumn::make('amount')->sortable(),
                TextColumn::make('currency_code'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([ ViewAction::make() ]);
    }
}