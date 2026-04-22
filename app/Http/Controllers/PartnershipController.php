<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Partner;
use App\Models\PartnerGood;
use App\Models\PartnershipRecord;

class PartnershipController extends Controller {
    // جلب كل بيانات الشراكة للموبايل دفعة واحدة
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
                        'good_id' => $item->partner_good_id, // 👈 إعادتها للتطبيق باسم good_id
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
        $partner = $request->user()->partners()->create($data);
        return response()->json(['id' => $partner->id]);
    }
    public function updatePartner(Request $request, $id) {
        $request->user()->partners()->findOrFail($id)->update($request->only(['name', 'profit_share_percentage']));
        return response()->json(true);
    }
    public function deletePartner(Request $request, $id) {
        $request->user()->partners()->findOrFail($id)->delete();
        return response()->json(true);
    }

    public function syncGood(Request $request) {
        $good = PartnerGood::create($request->only(['partner_id', 'name', 'cost_price']));
        return response()->json(['id' => $good->id]);
    }
    public function updateGood(Request $request, $id) {
        PartnerGood::findOrFail($id)->update($request->only(['name', 'cost_price']));
        return response()->json(true);
    }
    public function deleteGood($id) {
        PartnerGood::findOrFail($id)->delete();
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

        $record = PartnershipRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $request->record_date,
        ]);

        $item = $record->items()->create([
            'partner_good_id' => $request->good_id, // 👈 تحويل الاسم ليتطابق مع قاعدة البيانات
            'quantity' => $request->quantity,
            'selling_price' => $request->selling_price,
            'cost_price_at_sale' => $request->cost_price_at_sale,
        ]);

        return response()->json(['id' => $record->id, 'item_id' => $item->id]);
    }

    public function deleteRecordItem($id) {
        \App\Models\PartnershipRecordItem::findOrFail($id)->delete();
        return response()->json(true);
    }
}