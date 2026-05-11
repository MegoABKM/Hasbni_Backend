<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Plan;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'SaaS Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Select::make('role')
                    ->options([
                        'shop_owner' => 'Shop Owner',
                        'super_admin' => 'Super Admin',
                    ])->required()->default('shop_owner'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('subscription.plan.name')
                    ->label('Current Plan')
                    ->badge()
                    ->color('success'),
                TextColumn::make('subscription.ends_at')
                    ->label('Expires At')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Action::make('assign_plan')
                    ->label('Assign Plan')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->form([
                        Select::make('plan_id')
                            ->label('Select Plan')
                            ->options(Plan::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Select::make('billing_cycle')
                            ->options(['monthly' => 'Monthly', 'yearly' => 'Yearly', 'lifetime' => 'Lifetime'])
                            ->required(),
                        DatePicker::make('ends_at')
                            ->label('Expiration Date')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->subscription()->updateOrCreate(
                            ['user_id' => $record->id],
                            [
                                'plan_id' => $data['plan_id'],
                                'status' => 'active',
                                'billing_cycle' => $data['billing_cycle'],
                                'starts_at' => now(),
                                'ends_at' => $data['ends_at'],
                            ]
                        );
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}