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

        $salesQuery = Sale::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', '!=', 'voided');

        $totalRevenueUsd = $salesQuery->sum(DB::raw('total_price / CASE WHEN rate_to_usd_at_sale > 0 THEN rate_to_usd_at_sale ELSE 1 END'));
        $rawTotalProfitUsd = $salesQuery->sum('total_profit');
        $totalDiscountUsd = $salesQuery->sum(DB::raw('discount_amount / CASE WHEN rate_to_usd_at_sale > 0 THEN rate_to_usd_at_sale ELSE 1 END'));

        // 🚨 خصم ربح الشريك من المعادلة ليكون صافي ربح المتجر متطابق 100% 🚨
        $partnerShareUsd = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->join('partners as part', 'p.partner_id', '=', 'part.id')
            ->where('s.user_id', $user->id)
            ->whereBetween('s.created_at', [$start, $end])
            ->where('s.payment_status', '!=', 'voided')
            ->sum(DB::raw('((si.price_at_sale / CASE WHEN s.rate_to_usd_at_sale > 0 THEN s.rate_to_usd_at_sale ELSE 1 END) - si.cost_price_at_sale) * (si.quantity_sold - si.returned_quantity) * (part.profit_share_percentage / 100.0)'));

        $totalProfitUsd = $rawTotalProfitUsd - $totalDiscountUsd - $partnerShareUsd;

        $totalExpensesUsd = Expense::where('user_id', $user->id)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        $totalWithdrawalsUsd = OwnerWithdrawal::where('user_id', $user->id)
            ->whereBetween('withdrawal_date', [$start, $end])
            ->sum('amount');

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