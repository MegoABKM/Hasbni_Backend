<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncController extends Controller
{
    public function delta(Request $request)
    {
        $user = $request->user();
        
        // وقت آخر مزامنة تم إرسالها من الهاتف
        $sinceParam = $request->query('since', '1970-01-01T00:00:00Z');
        $since = Carbon::parse($sinceParam)->setTimezone('UTC')->format('Y-m-d H:i:s');

        $data = [];

        // 1. الجداول الرئيسية التي تحتوي مباشرة على user_id
        $parentTables = [
            'products', 'sales', 'customers', 'suppliers', 'expenses', 
            'withdrawals', 'product_categories', 'expense_categories', 
            'employees', 'partners', 'partner_goods', 'partnership_records', 
            'cash_drawers', 'cash_transactions'
        ];

        foreach ($parentTables as $table) {
            if (Schema::hasTable($table)) {
                $query = DB::table($table)
                    ->where('user_id', $user->id)
                    ->where('updated_at', '>=', $since);

                if (Schema::hasColumn($table, 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }

                $data[$table] = $query->get();
            }
        }

        // 2. الجداول الفرعية (Child Tables) التي لا تحتوي على user_id ويتم جلبها عبر الـ JOIN
        
        // تفاصيل الفواتير
        $data['sale_items'] = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.user_id', $user->id)
            ->where('sale_items.updated_at', '>=', $since)
            ->select('sale_items.*')
            ->get();

        // دفعات العملاء
        $data['customer_payments'] = DB::table('customer_payments')
            ->join('customers', 'customer_payments.customer_id', '=', 'customers.id')
            ->where('customers.user_id', $user->id)
            ->where('customer_payments.updated_at', '>=', $since)
            ->select('customer_payments.*')
            ->get();

        // دفعات الموردين
        $data['supplier_payments'] = DB::table('supplier_payments')
            ->join('suppliers', 'supplier_payments.supplier_id', '=', 'suppliers.id')
            ->where('suppliers.user_id', $user->id)
            ->where('supplier_payments.updated_at', '>=', $since)
            ->select('supplier_payments.*')
            ->get();

        // حركات المخزون
        $data['inventory_movements'] = DB::table('inventory_movements')
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->where('products.user_id', $user->id)
            ->where('inventory_movements.updated_at', '>=', $since)
            ->select('inventory_movements.*')
            ->get();

        // تفاصيل سجلات الشراكة
        $data['partnership_record_items'] = DB::table('partnership_record_items')
            ->join('partnership_records', 'partnership_record_items.partnership_record_id', '=', 'partnership_records.id')
            ->where('partnership_records.user_id', $user->id)
            ->where('partnership_record_items.updated_at', '>=', $since)
            ->select('partnership_record_items.*')
            ->get();

        // البروفايل الخاص بالمتجر
        $data['profiles'] = DB::table('profiles')
            ->where('user_id', $user->id)
            ->where('updated_at', '>=', $since)
            ->get();

        // 3. جلب السجلات المحذوفة (Tombstones / Soft Deletes)
        $deletedRecords = [];
        $softDeletedTables = ['products', 'sales', 'customers', 'suppliers', 'expenses', 'withdrawals', 'product_categories', 'expense_categories'];

        foreach ($softDeletedTables as $table) {
            if (Schema::hasTable($table)) {
                $trashedIds = DB::table($table)
                    ->where('user_id', $user->id)
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>=', $since)
                    ->pluck('id')
                    ->toArray();

                if (!empty($trashedIds)) {
                    $deletedRecords[] = [
                        'table' => $table,
                        'ids' => $trashedIds
                    ];
                }
            }
        }

        // إرفاق المحذوفات من السيرفر كـ Tombstones ليمسحها الموبايل
        $data['deleted_records'] = $deletedRecords;

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}