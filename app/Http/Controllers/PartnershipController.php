<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Partner;
use App\Models\PartnerGood;
use App\Models\PartnershipRecord;

class PartnershipController extends Controller {
    // جلب كل بيانات الشراكة للموبايل دفعة واحدة
    public function pull(Request $request) {
        $user = $request->user();
        return response()->json([
            'partners' => $user->partners()->with('goods')->get(),
            'records' => $user->partnershipRecords()->with('items')->get()
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

    public function syncRecord(Request $request) {
        $record = $request->user()->partnershipRecords()->firstOrCreate(['record_date' => $request->record_date]);
        $item = $record->items()->create($request->only(['good_id', 'quantity', 'selling_price', 'cost_price_at_sale']));
        return response()->json(['id' => $record->id, 'item_id' => $item->id]);
    }
    public function deleteRecordItem($id) {
        \App\Models\PartnershipRecordItem::findOrFail($id)->delete();
        return response()->json(true);
    }
}