<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ProductsRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\SalesRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\AuditLogsRelationManager; 
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\Action; 
use Illuminate\Database\Eloquent\Builder;

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

    // 🚀 جلب البيانات المسبق لتسريع الجدول وعمل عمود الـ DB Weight
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['sales', 'products', 'cashTransactions']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create'),
            Select::make('role')->options(['shop_owner' => 'Shop Owner', 'super_admin' => 'Super Admin'])->required(),
            Toggle::make('is_banned')->label('Ban User')->onColor('danger')->offColor('success'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(), // 👈 يجعله قابلاً للترتيب

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(), // 👈 يجعله قابلاً للترتيب
                
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                
                IconColumn::make('is_banned')
                    ->boolean()
                    ->label('Banned')
                    ->sortable(),
                
                // 🚀 عمود ثقل البيانات مع ترتيب SQL مخصص
                TextColumn::make('data_weight')
                    ->label('DB Weight (Records)')
                    ->getStateUsing(fn (User $record) => 
                        ($record->sales_count ?? 0) + 
                        ($record->products_count ?? 0) + 
                        ($record->cash_transactions_count ?? 0)
                    )
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderByRaw('(COALESCE(sales_count, 0) + COALESCE(products_count, 0) + COALESCE(cash_transactions_count, 0)) ' . $direction);
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 5000 ? 'danger' : ($state > 1000 ? 'warning' : 'gray'))
                    ->tooltip('اضغط على عنوان العمود لترتيب المتاجر حسب الحجم'),

                TextColumn::make('updated_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('subscription.plan.name')
                    ->label('Current Plan')
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
            ->recordActions([
                // زر الانتقال إلى لوحة بيانات العميل
                Action::make('view_tenant_data')
                    ->label('Tenant Data')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->color('info')
                    ->url(fn (User $record): string => static::getUrl('tenant-data', ['record' => $record])),

                EditAction::make(),
                
                // زر إنهاء الجلسات
                Action::make('revoke_sessions')
                    ->label('Force Logout')
                    ->icon('heroicon-o-power')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Force Logout User')
                    ->modalDescription('Delete all access tokens? They will be logged out from all devices immediately.')
                    ->action(fn (User $record) => $record->tokens()->delete()),

                // توليد ملف .SQL للعميل
                Action::make('export_sql')
                    ->label('Export SQL')
                    ->icon('heroicon-o-circle-stack')
                    ->color('warning')
                    ->action(function (User $record) {
                        $sql = "-- Backup Script for {$record->name} ({$record->email})\n";
                        $sql .= "-- Generated at: " . now()->format('Y-m-d H:i:s') . "\n";
                        $sql .= "BEGIN TRANSACTION;\n\n";

                        // Products
                        $sql .= "-- Table: products\n";
                        foreach ($record->products as $p) {
                            $name = str_replace("'", "''", $p->name);
                            $barcode = $p->barcode ? "'" . str_replace("'", "''", $p->barcode) . "'" : "NULL";
                            $sql .= "INSERT INTO products (server_id, name, barcode, quantity, cost_price, selling_price, created_at, sync_status) VALUES ({$p->id}, '{$name}', {$barcode}, {$p->quantity}, {$p->cost_price}, {$p->selling_price}, '{$p->created_at}', 1);\n";
                        }
                        $sql .= "\n";

                        // Customers (Debts)
                        $sql .= "-- Table: customers\n";
                        foreach ($record->customers as $c) {
                            $name = str_replace("'", "''", $c->name);
                            $phone = $c->phone ? "'" . str_replace("'", "''", $c->phone) . "'" : "NULL";
                            $sql .= "INSERT INTO customers (server_id, name, phone, balance, sync_status) VALUES ({$c->id}, '{$name}', {$phone}, {$c->balance}, 1);\n";
                        }
                        $sql .= "\n";

                        // Expenses
                        $sql .= "-- Table: expenses\n";
                        foreach ($record->expenses as $e) {
                            $desc = str_replace("'", "''", $e->description);
                            $sql .= "INSERT INTO expenses (server_id, description, amount, amount_in_currency, currency_code, expense_date, sync_status) VALUES ({$e->id}, '{$desc}', {$e->amount}, {$e->amount_in_currency}, '{$e->currency_code}', '{$e->expense_date}', 1);\n";
                        }

                        $sql .= "\nCOMMIT;\n";

                        $fileName = 'backup_' . preg_replace('/[^a-zA-Z0-9]/', '_', $record->name) . '.sql';

                        return response()->streamDownload(function () use ($sql) {
                            echo $sql;
                        }, $fileName, ['Content-Type' => 'application/sql']);
                    }),
            ]); 
    }

    public static function getRelations(): array
    {
        return [
            SubscriptionsRelationManager::class,
            PaymentsRelationManager::class,
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