<?php
namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

        // Anti-Duplication by Name
        $existing = $request->user()->suppliers()->where('name', $validated['name'])->first();
        if ($existing) {
            return $existing;
        }

        return $request->user()->suppliers()->create($validated);
    }

    public function update(Request $request, int $id) {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'balance' => 'sometimes|numeric',
        ]);
        $supplier = $request->user()->suppliers()->findOrFail($id);
        $supplier->update($validated);
        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function destroy(Request $request, int $id) {
        $request->user()->suppliers()->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function storePayment(Request $request, int $id) {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date'
        ]);

        $supplier = $request->user()->suppliers()->findOrFail($id);
        $clientDate = Carbon::parse($validated['payment_date'])->format('Y-m-d H:i:s');

        // Anti-Duplication for Payments
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

        return response()->json(['id' => $payment->id]);
    }

    public function getPayments(Request $request) {
        return \App\Models\SupplierPayment::whereHas('supplier', function($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->get();
    }
}