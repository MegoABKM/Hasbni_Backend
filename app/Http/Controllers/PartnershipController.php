<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Partner;
use App\Models\PartnerGood;
use App\Models\PartnershipRecord;
use App\Events\ShopDataUpdated;

class PartnershipController extends Controller {
    
    public function pull(Request $request)
    {
        $userId = $request->user()->id;

        $partners = \App\Models\Partner::where('user_id', $userId)->with('goods')->get();
        $records = \App\Models\PartnershipRecord::where('user_id', $userId)->with('items')->get();

        $formattedRecords = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'record_date' => $record->record_date,
                'items' => $record->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'good_id' => $item->partner_good_id,
                        'quantity' => $item->quantity,
                        'selling_price' => $item->selling_price,
                        'cost_price_at_sale' => $item->cost_price_at_sale,
                    ];
                }),
            ];
        });

        return response()->json([
            'partners' => $partners,
            'records' => $formattedRecords
        ]);
    }

    public function syncPartner(Request $request) {
        $data = $request->validate(['name' => 'required', 'profit_share_percentage' => 'required']);
        $user = $request->user();

        $partner = $user->partners()->firstOrCreate(
            ['name' => $data['name']], 
            ['profit_share_percentage' => $data['profit_share_percentage']]
        );
        
        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_updated'));
        }

        return response()->json(['id' => $partner->id]);
    }

    public function updatePartner(Request $request, $id) {
        $user = $request->user();
        $user->partners()->findOrFail($id)->update($request->only(['name', 'profit_share_percentage']));
        
        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_updated'));
        }

        return response()->json(true);
    }

    public function deletePartner(Request $request, $id) {
        $user = $request->user();
        $user->partners()->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_updated'));
        }

        return response()->json(true);
    }

    public function syncGood(Request $request) {
        $user = $request->user();
        $good = PartnerGood::firstOrCreate(
            ['partner_id' => $request->partner_id, 'name' => $request->name],
            ['cost_price' => $request->cost_price]
        );

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_good_updated'));
        }

        return response()->json(['id' => $good->id]);
    }

    public function updateGood(Request $request, $id) {
        $user = $request->user();
        PartnerGood::findOrFail($id)->update($request->only(['name', 'cost_price']));
        
        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_good_updated'));
        }

        return response()->json(true);
    }

    public function deleteGood(Request $request, $id) {
        $user = $request->user();
        PartnerGood::findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_good_updated'));
        }

        return response()->json(true);
    }

    public function syncRecord(Request $request)
    {
        $request->validate([
            'record_date' => 'required|date',
            'good_id' => 'required|exists:partner_goods,id',
            'quantity' => 'required|integer',
            'selling_price' => 'required|numeric',
            'cost_price_at_sale' => 'required|numeric',
        ]);

        $user = $request->user();

        $record = PartnershipRecord::firstOrCreate([
            'user_id' => $user->id,
            'record_date' => $request->record_date,
        ]);

        $item = $record->items()->firstOrCreate(
            [
                'partner_good_id' => $request->good_id,
                'quantity' => $request->quantity,
                'selling_price' => $request->selling_price,
            ],
            [
                'cost_price_at_sale' => $request->cost_price_at_sale,
            ]
        );

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_record_updated'));
        }

        return response()->json(['id' => $record->id, 'item_id' => $item->id]);
    }

    public function deleteRecordItem(Request $request, $id) {
        $user = $request->user();
        \App\Models\PartnershipRecordItem::findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_record_updated'));
        }

        return response()->json(true);
    }
}