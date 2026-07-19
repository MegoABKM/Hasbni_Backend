<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncController extends Controller
{
    public function delta(Request $request)
    {
        $user = $request->user();

        $sinceParam = $request->query('since', '1970-01-01T00:00:00Z');
        
        // 🚀 طرح 5 ثوانٍ كمعامل أمان لتفادي أي تأخير في معالجة العمليات المتزامنة في نفس اللحظة
        try {
            $since = Carbon::parse($sinceParam)->setTimezone('UTC')->subSeconds(5)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'invalid_since_parameter',
            ], 422);
        }

        $data = [];

        $parentTables = [
            'products',
            'sales',
            'customers',
            'suppliers',
            'expenses',
            'withdrawals',
            'owner_withdrawals',
            'product_categories',
            'expense_categories',
            'employees',
            'partners',
            'partner_goods',
            'partnership_records',
            'cash_drawers',
            'cash_transactions',
        ];

        foreach ($parentTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);

            if (Schema::hasColumn($table, 'user_id')) {
                $query->where('user_id', $user->id);
            }

            if (Schema::hasColumn($table, 'updated_at')) {
                $query->where('updated_at', '>=', $since);
            }

            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $data[$table] = $query->get();
        }

        if ($this->tableHasColumns('sale_items', ['sale_id']) &&
            $this->tableHasColumns('sales', ['id', 'user_id'])) {
            $data['sale_items'] = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.user_id', $user->id)
                ->when(Schema::hasColumn('sale_items', 'updated_at'), function ($query) use ($since) {
                    return $query->where('sale_items.updated_at', '>=', $since);
                })
                ->select('sale_items.*')
                ->get();
        }

        if ($this->tableHasColumns('customer_payments', ['customer_id']) &&
            $this->tableHasColumns('customers', ['id', 'user_id'])) {
            $data['customer_payments'] = DB::table('customer_payments')
                ->join('customers', 'customer_payments.customer_id', '=', 'customers.id')
                ->where('customers.user_id', $user->id)
                ->when(Schema::hasColumn('customer_payments', 'updated_at'), function ($query) use ($since) {
                    return $query->where('customer_payments.updated_at', '>=', $since);
                })
                ->select('customer_payments.*')
                ->get();
        }

        if ($this->tableHasColumns('supplier_payments', ['supplier_id']) &&
            $this->tableHasColumns('suppliers', ['id', 'user_id'])) {
            $data['supplier_payments'] = DB::table('supplier_payments')
                ->join('suppliers', 'supplier_payments.supplier_id', '=', 'suppliers.id')
                ->where('suppliers.user_id', $user->id)
                ->when(Schema::hasColumn('supplier_payments', 'updated_at'), function ($query) use ($since) {
                    return $query->where('supplier_payments.updated_at', '>=', $since);
                })
                ->select('supplier_payments.*')
                ->get();
        }

        if ($this->tableHasColumns('inventory_movements', ['product_id']) &&
            $this->tableHasColumns('products', ['id', 'user_id'])) {
            $data['inventory_movements'] = DB::table('inventory_movements')
                ->join('products', 'inventory_movements.product_id', '=', 'products.id')
                ->where('products.user_id', $user->id)
                ->when(Schema::hasColumn('inventory_movements', 'updated_at'), function ($query) use ($since) {
                    return $query->where('inventory_movements.updated_at', '>=', $since);
                })
                ->select('inventory_movements.*')
                ->get();
        }

        if ($this->tableHasColumns('partnership_record_items', ['partnership_record_id']) &&
            $this->tableHasColumns('partnership_records', ['id', 'user_id'])) {
            $data['partnership_record_items'] = DB::table('partnership_record_items')
                ->join('partnership_records', 'partnership_record_items.partnership_record_id', '=', 'partnership_records.id')
                ->where('partnership_records.user_id', $user->id)
                ->when(Schema::hasColumn('partnership_record_items', 'updated_at'), function ($query) use ($since) {
                    return $query->where('partnership_record_items.updated_at', '>=', $since);
                })
                ->select('partnership_record_items.*')
                ->get();
        }

        if ($this->tableHasColumns('profiles', ['user_id'])) {
            $data['profiles'] = DB::table('profiles')
                ->where('user_id', $user->id)
                ->when(Schema::hasColumn('profiles', 'updated_at'), function ($query) use ($since) {
                    return $query->where('updated_at', '>=', $since);
                })
                ->get();
        }

        $deletedRecords = [];
        $softDeletedTables = [
            'products',
            'sales',
            'customers',
            'suppliers',
            'expenses',
            'withdrawals',
            'owner_withdrawals',
            'product_categories',
            'expense_categories',
        ];

        foreach ($softDeletedTables as $table) {
            if (!Schema::hasTable($table) ||
                !Schema::hasColumn($table, 'deleted_at') ||
                !Schema::hasColumn($table, 'id')) {
                continue;
            }

            $query = DB::table($table)
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '>=', $since);

            if (Schema::hasColumn($table, 'user_id')) {
                $query->where('user_id', $user->id);
            }

            $trashedIds = $query->pluck('id')->toArray();

            if (!empty($trashedIds)) {
                $deletedRecords[] = [
                    'table' => $table,
                    'ids' => $trashedIds,
                ];
            }
        }

        $data['deleted_records'] = $deletedRecords;

        // 🚀 هنا السحر: نرسل وقت السيرفر الفعلي بالـ UTC لكي يعتمده تطبيق الموبايل كمرجع وحيد مستقل عن ساعة الهاتف
        return response()->json([
            'success' => true,
            'data' => $data,
            'server_time' => now()->utc()->toIso8601String(), 
        ], 200);
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
}
