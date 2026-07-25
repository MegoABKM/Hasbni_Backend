<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class PurgeTenantDataJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public readonly int $tenantId) {}

    public function handle(): void
    {
        $tenant = User::query()->find($this->tenantId);

        if ($tenant === null) {
            return;
        }

        if (! $tenant->isTenant()) {
            throw new RuntimeException("Refusing to purge non-tenant user {$this->tenantId}.");
        }

        DB::transaction(function () use ($tenant): void {
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $tenant->getKey())
                    ->delete();
            }

            foreach ($this->tenantTables() as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                    DB::table($table)->where('user_id', $tenant->getKey())->delete();
                }
            }

            $tenant->tokens()->delete();
            $tenant->delete();
        }, 3);

        Log::notice('Tenant data was permanently purged.', ['tenant_id' => $this->tenantId]);
    }

    /**
     * Explicit allow-list prevents global system tables and KPI snapshots from
     * ever being touched by this destructive job.
     *
     * @return array<int, string>
     */
    private function tenantTables(): array
    {
        return [
            'sale_items',
            'customer_payments',
            'supplier_payments',
            'cash_transactions',
            'inventory_movements',
            'partnership_record_items',
            'products',
            'product_categories',
            'sales',
            'customers',
            'suppliers',
            'expenses',
            'expense_categories',
            'owner_withdrawals',
            'cash_drawers',
            'partnership_records',
            'partner_goods',
            'partners',
            'employees',
            'exchange_rates',
            'fcm_tokens',
            'deleted_records',
            'sessions',
            'support_tickets',
            'payments',
            'subscriptions',
            'tenant_feature_flags',
            'profiles',
            'audit_logs',
        ];
    }
}
