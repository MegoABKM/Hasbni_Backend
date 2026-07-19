<?php

namespace App\Filament\Resources;

use App\Models\SupportTicket;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-ticket';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Support & Help');
    }

    public static function getNavigationLabel(): string
    {
        return __('Support Tickets');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->label(__('Customer'))
                ->disabled(),

            TextInput::make('subject')
                ->label(__('Subject'))
                ->disabled(),

            Textarea::make('message')
                ->label(__('Message'))
                ->disabled()
                ->columnSpanFull(),

            Select::make('status')
                ->label(__('Status'))
                ->options([
                    'open' => __('Open'),
                    'answered' => __('Answered'),
                    'closed' => __('Closed'),
                ])
                ->required(),

            Textarea::make('admin_reply')
                ->label(__('Admin Reply'))
                ->helperText(__('Replying to an open ticket marks it as answered.'))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label(__('Customer'))->searchable(),
                TextColumn::make('subject')->label(__('Subject'))->limit(30)->searchable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'answered' => 'success',
                        'closed' => 'gray',
                    }),
                TextColumn::make('created_at')->label(__('Created'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'open' => __('Open'),
                        'answered' => __('Answered'),
                        'closed' => __('Closed'),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! empty($data['admin_reply']) && $data['status'] === 'open') {
                            $data['status'] = 'answered';
                        }

                        return $data;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SupportTicketResource\Pages\ManageSupportTickets::route('/'),
        ];
    }
}
