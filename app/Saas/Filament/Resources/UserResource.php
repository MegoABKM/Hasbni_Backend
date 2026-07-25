<?php

declare(strict_types=1);

namespace App\Saas\Filament\Resources;

use App\Jobs\PurgeTenantDataJob;
use App\Models\User;
use App\Saas\Filament\Resources\UserResource\Pages;
use App\Saas\Filament\Resources\UserResource\RelationManagers\AuditLogsRelationManager;
use App\Saas\Filament\Resources\UserResource\RelationManagers\FeatureFlagsRelationManager;
use App\Saas\Filament\Resources\UserResource\RelationManagers\PaymentsRelationManager;
use App\Saas\Filament\Resources\UserResource\RelationManagers\ProfileRelationManager;
use App\Saas\Filament\Resources\UserResource\RelationManagers\SubscriptionsRelationManager;
use App\Saas\Filament\Resources\UserResource\RelationManagers\TokensRelationManager;
use App\Support\RbacPermission;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tenants');
    }

    public static function getModelLabel(): string
    {
        return __('Tenant');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tenants');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->tenants()
            ->with('subscription.plan')
            ->withCount(['subscriptions', 'payments'])
            ->withSum([
                'payments as successful_payments_sum' => fn (Builder $query): Builder => $query->where('status', 'successful'),
            ], 'amount');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Tenant Profile'))
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
                        TextInput::make('phone')
                            ->label(__('Phone Number'))
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('country')
                            ->label(__('Country'))
                            ->maxLength(100),
                        TextInput::make('business_type')
                            ->label(__('Industry'))
                            ->maxLength(100),
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ]),
                    Toggle::make('is_banned')
                        ->label(__('Suspend Tenant'))
                        ->onColor('danger')
                        ->offColor('success'),
                    Hidden::make('role')->default('tenant'),
                    Hidden::make('account_type')->default(User::ACCOUNT_TYPE_TENANT),
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
                TextColumn::make('country')
                    ->label(__('Country'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->placeholder(__('Unspecified')),
                TextColumn::make('subscription.plan.name')
                    ->label(__('Current Plan'))
                    ->getStateUsing(fn (User $record): string => $record->subscription?->plan?->name ?? __('Free'))
                    ->badge()
                    ->color(fn (string $state): string => $state === __('Free') ? 'gray' : 'success'),
                TextColumn::make('subscriptions_count')
                    ->label(__('Subscriptions'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('successful_payments_sum')
                    ->label(__('Total Revenue'))
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('is_banned')
                    ->label(__('Suspended'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Registered At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('Last Activity'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('country')
                    ->label(__('Country'))
                    ->options(fn (): array => User::query()
                        ->tenants()
                        ->whereNotNull('country')
                        ->where('country', '!=', '')
                        ->distinct()
                        ->orderBy('country')
                        ->pluck('country', 'country')
                        ->all()),
                SelectFilter::make('is_banned')
                    ->label(__('Account Status'))
                    ->options([
                        '0' => __('Active'),
                        '1' => __('Suspended'),
                    ]),
            ])
            ->recordActions([
                Action::make('view_tenant_data')
                    ->label(__('Tenant Details'))
                    ->icon('heroicon-o-presentation-chart-line')
                    ->color('info')
                    ->url(fn (User $record): string => static::getUrl('tenant-data', ['record' => $record])),
                EditAction::make(),
                Action::make('revoke_sessions')
                    ->label(__('Revoke All Sessions'))
                    ->icon('heroicon-o-power')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('Revoke All Sessions'))
                    ->modalDescription(__('The tenant will be signed out from every device immediately.'))
                    ->action(function (User $record): void {
                        $record->tokens()->delete();

                        Notification::make()
                            ->title(__('All sessions were revoked successfully.'))
                            ->success()
                            ->send();
                    }),
                static::impersonationAction(),
                static::purgeTenantAction(),
            ]);
    }

    public static function impersonationAction(): Action
    {
        return Action::make('impersonate_api_token')
            ->label(__('Impersonate (API Token)'))
            ->icon('heroicon-o-key')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('Generate Impersonation Token'))
            ->modalDescription(__('This creates a temporary API token that acts as this tenant.'))
            ->modalSubmitActionLabel(__('Generate Token'))
            ->visible(fn (User $record): bool => $record->isTenant()
                && (auth()->user()?->can(RbacPermission::IMPERSONATE_TENANT) ?? false))
            ->form([
                TextInput::make('password')
                    ->label(__('Confirm Your Password'))
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
                static::verifyAdminPassword(
                    (string) $data['password'],
                    RbacPermission::IMPERSONATE_TENANT,
                );

                $record->tokens()
                    ->where('name', 'admin_impersonation')
                    ->delete();

                $token = $record->createToken('admin_impersonation')->plainTextToken;

                Notification::make()
                    ->title(__('Impersonation token created'))
                    ->body(__('Copy this token and store it securely:')."\n\n{$token}")
                    ->warning()
                    ->persistent()
                    ->send();
            });
    }

    public static function purgeTenantAction(): Action
    {
        return Action::make('purge_tenant')
            ->label(__('Permanently Purge Tenant'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (User $record): bool => $record->isTenant()
                && (auth()->user()?->can(RbacPermission::PURGE_TENANT) ?? false))
            ->requiresConfirmation()
            ->modalHeading(__('Permanently Purge Tenant'))
            ->modalDescription(__('All tenant data will be permanently erased. This action cannot be undone.'))
            ->form([
                TextInput::make('password')
                    ->label(__('Confirm Your Password'))
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
                static::verifyAdminPassword(
                    (string) $data['password'],
                    RbacPermission::PURGE_TENANT,
                );
                PurgeTenantDataJob::dispatch((int) $record->getKey());

                Notification::make()
                    ->title(__('Tenant purge queued'))
                    ->success()
                    ->send();
            });
    }

    private static function verifyAdminPassword(string $password, string $permission): void
    {
        $admin = auth()->user();

        if (! $admin instanceof User
            || ! $admin->can($permission)
            || ! Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }
    }

    public static function getRelations(): array
    {
        return [
            ProfileRelationManager::class,
            SubscriptionsRelationManager::class,
            PaymentsRelationManager::class,
            TokensRelationManager::class,
            AuditLogsRelationManager::class,
            FeatureFlagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'tenant-data' => Pages\ViewTenantData::route('/{record}/tenant-data'),
        ];
    }
}
