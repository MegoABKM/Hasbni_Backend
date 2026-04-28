<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // جلب الحركات للهاتف الجديد
    public function index(Request $request) {
        return response()->json(
            $request->user()->inventoryMovements()->latest('created_at')->get()
        );
    }

    public function syncMovements(Request $request)
    {
        $user = $request->user();
        $responses = [];

        DB::transaction(function () use ($user, $request, &$responses) {
            if ($request->has('movements') && is_array($request->movements)) {
                foreach ($request->movements as $mov) {
                    
                    // FirstOrCreate تمنع التكرار إذا أرسل التطبيق نفس الحركة
                    $record = $user->inventoryMovements()->firstOrCreate(
                        [
                            'product_id' => $mov['product_id'],
                            'created_at' => $mov['created_at'],
                            'movement_type' => $mov['movement_type']
                        ],
                        [
                            'quantity_change' => $mov['quantity_change'],
                            'current_balance' => $mov['current_balance'],
                            'cost_price_at_time' => $mov['cost_price_at_time'] ?? 0,
                            'reference_id' => $mov['reference_id'] ?? 0,
                        ]
                    );

                    // نُرجع الـ ID لتطبيق فلاتر
                    $responses[] = [
                        'local_id' => $mov['local_id'],
                        'server_id' => $record->id
                    ];
                }
            }
        });
        return response()->json(['success' => true, 'synced' => $responses]);
    }
}