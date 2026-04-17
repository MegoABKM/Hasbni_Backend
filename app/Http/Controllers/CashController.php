<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                $user->cashTransactions()->createMany($request->transactions);
            }
        });
        return response()->json(['success' => true]);
    }

    // 👈 التعديل هنا: إضافة حركات الكاشيرية ليتم سحبها للهاتف الجديد
    public function getDrawers(Request $request)
    {
        return response()->json([
            'drawers' => $request->user()->cashDrawers()->get(['currency_code', 'balance']),
            'transactions' => $request->user()->cashTransactions()->get() // 👈 هذا السطر الجديد
        ]);
    }
}