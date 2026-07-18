<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Events\ShopDataUpdated;

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

        $user = $request->user();
        $existing = $user->customers()->where('name', $validated['name'])->first();
        if ($existing) return $existing;

        $customer = $user->customers()->create($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'customer_created', $customer->toArray(), $request->header('X-Device-ID')));
        }

        return $customer;
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'balance' => 'sometimes|numeric',
        ]);

        $user = $request->user();
        $customer = $user->customers()->findOrFail($id);
        $customer->update($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'customer_updated', $customer->toArray(), $request->header('X-Device-ID')));
        }

        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function destroy(Request $request, $id) {
        $user = $request->user();
        $user->customers()->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'customer_deleted', ['id' => $id], $request->header('X-Device-ID')));
        }

        return response()->json(['success' => true]);
    }

    public function storePayment(Request $request, $id) {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date'
        ]);

        $user = $request->user();
        $customer = $user->customers()->findOrFail($id);
        $clientDate = Carbon::parse($validated['payment_date'])->format('Y-m-d H:i:s');

        $existing = $customer->payments()->where('amount', $validated['amount'])->where('payment_date', $clientDate)->first();
        if ($existing) return response()->json(['id' => $existing->id]);

        $payment = $customer->payments()->create([
            'amount' => $validated['amount'],
            'payment_date' => $clientDate,
            'created_at' => $clientDate,
            'updated_at' => $clientDate,
        ]);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'customer_payment_created', $payment->toArray(), $request->header('X-Device-ID')));
        }

        return response()->json(['id' => $payment->id]);
    }

    public function getPayments(Request $request) {
        return \App\Models\CustomerPayment::whereHas('customer', function($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->get();
    }
}
