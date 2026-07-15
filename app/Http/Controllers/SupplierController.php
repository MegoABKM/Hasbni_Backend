<?php
namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Events\ShopDataUpdated;

class SupplierController extends Controller
{
    public function index(Request $request) {
        return $request->user()->suppliers()->latest()->get();
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'balance' => 'nullable|numeric',
        ]);

        $user = $request->user();
        $existing = $user->suppliers()->where('name', $validated['name'])->first();
        if ($existing) {
            return $existing;
        }

        $supplier = $user->suppliers()->create($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'supplier_created', [], $request->header('X-Device-ID'))); // 👈
        }

        return $supplier;
    }

    public function update(Request $request, int $id) {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'balance' => 'sometimes|numeric',
        ]);
        
        $user = $request->user();
        $supplier = $user->suppliers()->findOrFail($id);
        $supplier->update($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'supplier_updated', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function destroy(Request $request, int $id) {
        $user = $request->user();
        $user->suppliers()->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'supplier_deleted', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(['success' => true]);
    }

    public function storePayment(Request $request, int $id) {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date'
        ]);

        $user = $request->user();
        $supplier = $user->suppliers()->findOrFail($id);
        $clientDate = Carbon::parse($validated['payment_date'])->format('Y-m-d H:i:s');

        $existing = $supplier->payments()
            ->where('amount', $validated['amount'])
            ->where('payment_date', $clientDate)
            ->first();

        if ($existing) {
            return response()->json(['id' => $existing->id]);
        }

        $payment = $supplier->payments()->create([
            'amount' => $validated['amount'],
            'payment_date' => $clientDate,
            'created_at' => $clientDate,
            'updated_at' => $clientDate,
        ]);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'supplier_payment_synced', [], $request->header('X-Device-ID'))); // 👈
        }

        return response()->json(['id' => $payment->id]);
    }

    public function getPayments(Request $request) {
        return \App\Models\SupplierPayment::whereHas('supplier', function($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->get();
    }
}