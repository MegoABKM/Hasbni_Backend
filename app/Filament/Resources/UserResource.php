<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\DB; // 👈 استيراد DB مطلوب لعملية المسح
use App\Models\AuditLog;
use App\Models\User;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\ProductsRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\SalesRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\AuditLogsRelationManager; 
use App\Filament\Resources\UserResource\RelationManagers\TokensRelationManager; // 👈 استيراد إدارة الأجهزة

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
use Filament\Notifications\Notification; // 👈 استيراد الإشعارات لرسالة النجاح

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
            
            // 🚀 الحقول الجديدة في واجهة الإدارة
            TextInput::make('phone')->label('Phone Number'),
            TextInput::make('country')->label('Country'),
            TextInput::make('business_type')->label('Business Type'),

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
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                
                IconColumn::make('is_banned')
                    ->boolean()
                    ->label('Banned')
                    ->sortable(),
                    
                // 🚀 الأعمدة الجديدة
                TextColumn::make('country')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('business_type')
                    ->label('Industry')
                    ->searchable()
                    ->toggleable(),
                    
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
            ->filters([
                // 🚀 الفلاتر الجديدة لتحليل العملاء حسب الدولة ونوع التجارة
                SelectFilter::make('country')
                    ->options(fn () => User::pluck('country', 'country')->filter()->unique()->toArray())
                    ->label('Filter by Country'),

                SelectFilter::make('business_type')
                    ->options(fn () => User::pluck('business_type', 'business_type')->filter()->unique()->toArray())
                    ->label('Filter by Industry'),
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

                // 🚀 زر تصفير بيانات العميل (Soft Reset) 🚀
                Action::make('wipe_data')
                    ->label('Soft Reset')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Wipe Operational Data?')
                    ->modalDescription('⚠️ Warning: This will permanently delete ALL Sales, Products, Expenses, Customers, and Cash records for this user. Their Subscription, Payments, and Profile will remain intact. This action CANNOT be undone.')
                    ->modalSubmitActionLabel('Yes, Wipe Everything')
                    ->action(function (User $record) {
                        
                        DB::transaction(function () use ($record) {
                            // مسح البيانات التشغيلية (تعتمد على Cascade Deletes في حال وجود علاقات)
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

                            // 🛡️ أمان: تسجيل هذه العملية الخطيرة في الـ Audit Log لتعرف من قام بمسح بيانات العميل
                            AuditLog::create([
                                'user_id' => auth()->id(), // الأدمن الذي قام بالعملية
                                'event' => 'tenant_wiped',
                                'auditable_type' => User::class,
                                'auditable_id' => $record->id,
                                'new_values' => json_encode(['action' => 'Admin executed a Soft Reset (Wipe Data).']),
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ]);
                        });

                        Notification::make()
                            ->title('Tenant operational data wiped successfully.')
                            ->success()
                            ->send();
                    }),

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
            TokensRelationManager::class,    // 🚀 تم دمج الـ Relation Manager للأجهزة
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