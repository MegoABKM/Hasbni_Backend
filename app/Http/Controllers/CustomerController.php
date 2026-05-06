<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function index(Request $request) {
        return $request->user()->customers()->latest()->get();
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'balance' => 'nullable|numeric',
        ]);

        // Anti-Duplication: Check if customer with same name already exists
        $existing = $request->user()->customers()->where('name', $validated['name'])->first();
        if ($existing) {
            return $existing;
        }

        return $request->user()->customers()->create($validated);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'balance' => 'sometimes|numeric',
        ]);

        $customer = $request->user()->customers()->findOrFail($id);
        $customer->update($validated);
        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function destroy(Request $request, $id) {
        $request->user()->customers()->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function storePayment(Request $request, $id) {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date'
        ]);

        $customer = $request->user()->customers()->findOrFail($id);
        $clientDate = Carbon::parse($validated['payment_date'])->format('Y-m-d H:i:s');

        // Anti-Duplication for Payments
        $existing = $customer->payments()
            ->where('amount', $validated['amount'])
            ->where('payment_date', $clientDate)
            ->first();

        if ($existing) {
            return response()->json(['id' => $existing->id]);
        }

        $payment = $customer->payments()->create([
            'amount' => $validated['amount'],
            'payment_date' => $clientDate,
            'created_at' => $clientDate,
            'updated_at' => $clientDate,
        ]);

        return response()->json(['id' => $payment->id]);
    }

    public function getPayments(Request $request) {
        return \App\Models\CustomerPayment::whereHas('customer', function($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->get();
    }
}