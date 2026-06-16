<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashController extends Controller
{
    public function sync(Request $request)
    {
        $user = $request->user();
        
        DB::transaction(function () use ($user, $request) {
            if ($request->has('drawers')) {
                foreach ($request->drawers as $drawer) {
                    $user->cashDrawers()->updateOrCreate(
                        ['currency_code' => $drawer['currency_code']],
                        ['balance' => $drawer['balance']]
                    );
                }
            }
            
            if ($request->has('transactions') && is_array($request->transactions)) {
                foreach ($request->transactions as $t) {
                    $clientDate = isset($t['transaction_date']) 
                        ? Carbon::parse($t['transaction_date'])->format('Y-m-d H:i:s') 
                        : now()->format('Y-m-d H:i:s');

                    // 🚨 هنا الإصلاح: نقارن مع transaction_date بدلاً من created_at
                    $existing = $user->cashTransactions()
                        ->where('transaction_type', $t['transaction_type'])
                        ->where('amount', $t['amount'])
                        ->where('transaction_date', $clientDate) 
                        ->first();

                    if (!$existing) {
                        $user->cashTransactions()->create([
                            'transaction_type' => $t['transaction_type'],
                            'amount' => $t['amount'],
                            'currency_code' => $t['currency_code'],
                            'reference_id' => $t['reference_id'] ?? 0,
                            'employee_id' => $t['employee_id'] ?? null,
                            'transaction_date' => $clientDate,
                            'created_at' => $clientDate,
                            'updated_at' => $clientDate,
                        ]);
                    }
                }
            }
        });
        
        return response()->json(['success' => true]);
    }

    public function getDrawers(Request $request)
    {
        return response()->json([
            'drawers' => $request->user()->cashDrawers()->get(['currency_code', 'balance']),
            'transactions' => $request->user()->cashTransactions()->get()
        ]);
    }
}