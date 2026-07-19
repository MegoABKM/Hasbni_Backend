<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\ShopDataUpdated;

class CashController extends Controller
{
    public function sync(Request $request)
    {
        $request->validate([
            'drawers' => 'nullable|array',
            'drawers.*.currency_code' => 'required_with:drawers|string|max:3',
            'drawers.*.balance' => 'required_with:drawers|numeric',
            'transactions' => 'nullable|array',
            'transactions.*.transaction_type' => 'required_with:transactions|string|max:50',
            'transactions.*.amount' => 'required_with:transactions|numeric',
            'transactions.*.currency_code' => 'required_with:transactions|string|max:3',
            'transactions.*.reference_id' => 'nullable|integer',
            'transactions.*.employee_id' => 'nullable|integer',
            'transactions.*.transaction_date' => 'nullable|date',
        ]);

        $user = $request->user();
        $syncedTransactions = [];
        
        DB::transaction(function () use ($user, $request, &$syncedTransactions) {
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

                    $existing = $user->cashTransactions()
                        ->where('transaction_type', $t['transaction_type'])
                        ->where('amount', $t['amount'])
                        ->where('transaction_date', $clientDate) 
                        ->first();

                    if (!$existing) {
                        $tx = $user->cashTransactions()->create([
                            'transaction_type' => $t['transaction_type'],
                            'amount' => $t['amount'],
                            'currency_code' => $t['currency_code'],
                            'reference_id' => $t['reference_id'] ?? 0,
                            'employee_id' => $t['employee_id'] ?? null,
                            'transaction_date' => $clientDate,
                            'created_at' => $clientDate,
                            'updated_at' => $clientDate,
                        ]);
                        $syncedTransactions[] = $tx->toArray();
                    }
                }
            }
        });
        
        if ($user->hasRealtimeSyncFeature()) {
            // 🚀 إرسال الأرصدة والعمليات الجديدة بالكامل في البايلود
            event(new ShopDataUpdated($user->id, 'cash_synced', [
                'drawers' => $user->cashDrawers()->get(['currency_code', 'balance'])->toArray(),
                'transactions' => $syncedTransactions
            ]));
        }

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
