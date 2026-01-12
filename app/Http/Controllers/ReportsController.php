<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\OwnerWithdrawal;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function summary(Request $request) {
        $user = $request->user();
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        // 1. Revenue (Sales)
        // FIX: We must DIVIDE the stored local price by the rate to get back to USD.
        $sales = Sale::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();
            
        $totalRevenueUsd = $sales->sum(function($sale) {
            $rate = $sale->rate_to_usd_at_sale > 0 ? $sale->rate_to_usd_at_sale : 1;
            return $sale->total_price / $rate;
        });

        // 2. Profit is already stored in USD in the database
        $totalProfitUsd = $sales->sum('total_profit');

        // 3. Expenses (stored as USD 'amount' in DB)
        $totalExpensesUsd = Expense::where('user_id', $user->id)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        // 4. Withdrawals (stored as USD 'amount' in DB)
        $totalWithdrawalsUsd = OwnerWithdrawal::where('user_id', $user->id)
            ->whereBetween('withdrawal_date', [$start, $end])
            ->sum('amount');

        // 5. Inventory Value (Cost Price * Qty)
        // Cost price is static USD, so this is correct.
        $inventoryValueUsd = $user->products()
            ->sum(DB::raw('cost_price * quantity'));

        return [
            'total_revenue' => $totalRevenueUsd,
            'total_profit' => $totalProfitUsd,
            'total_expenses' => $totalExpensesUsd,
            'total_withdrawals' => $totalWithdrawalsUsd,
            'net_profit' => $totalProfitUsd - $totalExpensesUsd,
            'inventory_value' => $inventoryValueUsd
        ];
    }
}