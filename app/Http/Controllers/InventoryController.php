<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\ShopDataUpdated;

class InventoryController extends Controller
{
    public function index(Request $request) {
        return response()->json(
            $request->user()->inventoryMovements()->latest('created_at')->get()
        );
    }

    public function syncMovements(Request $request)
    {
        $request->validate([
            'movements' => 'nullable|array',
            'movements.*.local_id' => 'required_with:movements',
            'movements.*.product_id' => 'required_with:movements|integer',
            'movements.*.created_at' => 'required_with:movements|date',
            'movements.*.movement_type' => 'required_with:movements|string|max:50',
            'movements.*.quantity_change' => 'required_with:movements|integer',
            'movements.*.current_balance' => 'required_with:movements|integer',
            'movements.*.cost_price_at_time' => 'nullable|numeric',
            'movements.*.reference_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $responses = [];
        $syncedMovements = [];

        DB::transaction(function () use ($user, $request, &$responses, &$syncedMovements) {
            if ($request->has('movements') && is_array($request->movements)) {
                foreach ($request->movements as $mov) {
                    
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

                    $responses[] = [
                        'local_id' => $mov['local_id'],
                        'server_id' => $record->id
                    ];
                    $syncedMovements[] = $record->toArray();
                }
            }
        });

        if ($user->hasRealtimeSyncFeature() && !empty($syncedMovements)) {
             // 🚀 إرسال الحركات بالكامل للموبايل
            event(new ShopDataUpdated($user->id, 'inventory_synced', $syncedMovements));
        }

        return response()->json(['success' => true, 'synced' => $responses]);
    }
}
