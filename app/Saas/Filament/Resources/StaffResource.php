<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources;

use App\Models\User;
use App\Saas\Filament\Resources\StaffResource\Pages;
use App\Support\RbacPermission;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

final class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Staff');
    }

    public static function getModelLabel(): string
    {
        return __('Staff Member');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Staff');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->staff()
            ->with('roles');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Staff Account'))
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)),
                        Select::make('roles')
                            ->label(__('Roles'))
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('guard_name', 'web')
                                    ->orderBy('name'),
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->disabled(fn (): bool => ! (auth()->user()?->can(RbacPermission::ASSIGN_STAFF_ROLES) ?? false))
                            ->dehydrated(fn (): bool => auth()->user()?->can(RbacPermission::ASSIGN_STAFF_ROLES) ?? false)
                            ->required(),
                    ]),
                    Hidden::make('account_type')->default(User::ACCOUNT_TYPE_STAFF),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('roles.name')
                    ->label(__('Roles'))
                    ->badge()
                    ->separator(','),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->isNot(auth()->user())),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->authorizeIndividualRecords('delete'),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('ViewAny:StaffResource') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('View:StaffResource') ?? false;
    }

    public static function canCreate(): bool
    {
        return (auth()->user()?->can('Create:StaffResource') ?? false)
            && (auth()->user()?->can(RbacPermission::ASSIGN_STAFF_ROLES) ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('Update:StaffResource') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return $record->isNot(auth()->user())
            && (auth()->user()?->can('Delete:StaffResource') ?? false);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('DeleteAny:StaffResource') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
