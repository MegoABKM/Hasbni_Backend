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
            event(new ShopDataUpdated($user->id, 'partnership_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(['id' => $partner->id]);
    }

    public function updatePartner(Request $request, $id) {
        $user = $request->user();
        $user->partners()->findOrFail($id)->update($request->only(['name', 'profit_share_percentage']));
        
        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(true);
    }

    public function deletePartner(Request $request, $id) {
        $user = $request->user();
        $user->partners()->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(true);
    }

    public function syncGood(Request $request) {
        $user = $request->user();
        $data = $request->validate([
            'partner_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'cost_price' => 'required|numeric',
        ]);

        $partner = $user->partners()->findOrFail($data['partner_id']);
        $good = $partner->goods()->firstOrCreate(
            ['name' => $data['name']],
            ['cost_price' => $data['cost_price']]
        );

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_good_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(['id' => $good->id]);
    }

    public function updateGood(Request $request, $id) {
        $user = $request->user();
        $good = PartnerGood::whereHas('partner', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id);

        $good->update($request->validate([
            'name' => 'required|string|max:255',
            'cost_price' => 'required|numeric',
        ]));
        
        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_good_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(true);
    }

    public function deleteGood(Request $request, $id) {
        $user = $request->user();
        PartnerGood::whereHas('partner', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_good_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(true);
    }

    public function syncRecord(Request $request)
    {
        $request->validate([
            'record_date' => 'required|date',
            'good_id' => 'required|integer',
            'quantity' => 'required|integer',
            'selling_price' => 'required|numeric',
            'cost_price_at_sale' => 'required|numeric',
        ]);

        $user = $request->user();
        PartnerGood::whereHas('partner', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($request->good_id);

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
            event(new ShopDataUpdated($user->id, 'partnership_record_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(['id' => $record->id, 'item_id' => $item->id]);
    }

    public function deleteRecordItem(Request $request, $id) {
        $user = $request->user();
        \App\Models\PartnershipRecordItem::whereHas('record', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'partnership_record_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(true);
    }
}
