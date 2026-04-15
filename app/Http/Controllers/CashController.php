<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashController extends Controller
{
    // 1. الدالة السابقة: استقبال البيانات من الهاتف (Push)
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

    // 2. الدالة الجديدة: إرسال بيانات الخزينة للهاتف (Pull) 👈
    public function getDrawers(Request $request)
    {
        // نكتفي بإرجاع الأرصدة (Drawers) لأن الهاتف يحتاجها لمعرفة النقد المتاح
        // (لا داعي لإرجاع كل الحركات القديمة لتوفير مساحة الهاتف، الرصيد هو الأهم)
        return response()->json([
            'drawers' => $request->user()->cashDrawers()->get(['currency_code', 'balance'])
        ]);
    }
}