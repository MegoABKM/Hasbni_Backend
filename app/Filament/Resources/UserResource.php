<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Models\User;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ProductsRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\SalesRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\AuditLogsRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\TokensRelationManager; 

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\Action; 
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-users'; }
    public static function getNavigationGroup(): ?string { return __('SaaS Management'); }
    public static function getNavigationLabel(): string { return __('Users & Tenants'); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['sales', 'products', 'cashTransactions']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->label(__('Name'))->required(),
            TextInput::make('email')->label(__('Email'))->email()->required(),
            TextInput::make('phone')->label(__('Phone Number')),
            TextInput::make('country')->label(__('Country')),
            TextInput::make('business_type')->label(__('Business Type')),

            TextInput::make('password')
                ->label(__('Password'))
                ->password()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create'),
            Select::make('role')
                ->label(__('Role'))
                ->options(['shop_owner' => __('Shop Owner'), 'super_admin' => __('Super Admin')])->required(),
            Toggle::make('is_banned')->label(__('Ban User'))->onColor('danger')->offColor('success'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('Email'))->searchable()->sortable(),
                TextColumn::make('role')->label(__('Role'))->badge()->sortable(),
                IconColumn::make('is_banned')->label(__('Banned'))->boolean()->sortable(),
                    
                TextColumn::make('country')->label(__('Country'))->searchable()->sortable()->badge()->color('info'),
                TextColumn::make('business_type')->label(__('Industry'))->searchable()->toggleable(),
                    
                TextColumn::make('data_weight')
                    ->label(__('DB Weight (Records)'))
                    ->getStateUsing(fn (User $record) => 
                        ($record->sales_count ?? 0) + 
                        ($record->products_count ?? 0) + 
                        ($record->cash_transactions_count ?? 0)
                    )
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderByRaw('(COALESCE(sales_count, 0) + COALESCE(products_count, 0) + COALESCE(cash_transactions_count, 0)) ' . $direction);
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 5000 ? 'danger' : ($state > 1000 ? 'warning' : 'gray')),

                TextColumn::make('updated_at')->label(__('Last Sync'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('subscription.plan.name')
                    ->label(__('Current Plan'))
                    ->getStateUsing(function (User $record) {
                        $sub = $record->subscription;
                        if (!$sub || $sub->status === 'expired' || ($sub->ends_at && Carbon::parse($sub->ends_at)->isPast())) {
                            return 'Free';
                        }
                        return $sub->plan->name ?? 'Free';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Free' ? 'gray' : 'success')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country')
                    ->options(fn () => User::pluck('country', 'country')->filter()->unique()->toArray())
                    ->label(__('Filter by Country')),

                SelectFilter::make('business_type')
                    ->options(fn () => User::pluck('business_type', 'business_type')->filter()->unique()->toArray())
                    ->label(__('Filter by Industry')),
            ])
            ->recordActions([
                Action::make('view_tenant_data')
                    ->label(__('Tenant Data'))
                    ->icon('heroicon-o-presentation-chart-line')
                    ->color('info')
                    ->url(fn (User $record): string => static::getUrl('tenant-data', ['record' => $record])),

                EditAction::make(),
                
                Action::make('revoke_sessions')
                    ->label(__('Force Logout'))
                    ->icon('heroicon-o-power')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('Force Logout User'))
                    ->modalDescription(__('Delete all access tokens? They will be logged out from all devices immediately.'))
                    ->action(fn (User $record) => $record->tokens()->delete()),

                Action::make('wipe_data')
                    ->label(__('Soft Reset'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('Wipe Operational Data?'))
                    ->modalDescription(__('⚠️ Warning: This will permanently delete ALL Sales, Products, Expenses, Customers, and Cash records for this user. Their Subscription, Payments, and Profile will remain intact. This action CANNOT be undone.'))
                    ->modalSubmitActionLabel(__('Yes, Wipe Everything'))
                    ->action(function (User $record) {
                        DB::transaction(function () use ($record) {
                            $record->sales()->delete();
                            $record->products()->delete();
                            $record->expenses()->delete();
                            $record->withdrawals()->delete();
                            $record->customers()->delete();
                            $record->suppliers()->delete();
                            $record->partners()->delete();
                            $record->partnershipRecords()->delete();
                            $record->cashTransactions()->delete();
                            $record->cashDrawers()->delete();
                            $record->employees()->delete();
                            $record->productCategories()->delete();
                            $record->expenseCategories()->delete();
                            $record->inventoryMovements()->delete();

                            AuditLog::create([
                                'user_id' => auth()->id(), 
                                'event' => 'tenant_wiped',
                                'auditable_type' => User::class,
                                'auditable_id' => $record->id,
                                'new_values' => json_encode(['action' => 'Admin executed a Soft Reset (Wipe Data).']),
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ]);
                        });

                        Notification::make()
                            ->title(__('Tenant operational data wiped successfully.'))
                            ->success()
                            ->send();
                    }),

                Action::make('export_sql')
                    ->label(__('Export SQL'))
                    ->icon('heroicon-o-circle-stack')
                    ->color('warning')
                    ->action(function (User $record) {
                        // الكود الداخلي للـ SQL كما هو
                        $sql = "-- Backup Script for {$record->name}\n";
                        $fileName = 'backup_' . preg_replace('/[^a-zA-Z0-9]/', '_', $record->name) . '.sql';
                        return response()->streamDownload(fn () => print($sql), $fileName, ['Content-Type' => 'application/sql']);
                    }),
            ]); 
    }

    public static function getRelations(): array
    {
        return [
            SubscriptionsRelationManager::class,
            PaymentsRelationManager::class,
            TokensRelationManager::class, 
            ProductsRelationManager::class, 
            SalesRelationManager::class,    
            AuditLogsRelationManager::class,
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