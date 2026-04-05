<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

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

        return $request->user()->customers()->create($validated);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'balance' => 'sometimes|numeric', // في حال قام العميل بتسديد جزء من دينه
        ]);

        $customer = $request->user()->customers()->findOrFail($id);
        $customer->update($validated);
        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function destroy(Request $request, $id) {
        $request->user()->customers()->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
// لاستقبال دفعة جديدة من فلاتر
    public function storePayment(Request $request, $id) {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date'
        ]);
        $customer = $request->user()->customers()->findOrFail($id);
        $payment = $customer->payments()->create($validated);
        return response()->json(['id' => $payment->id]);
    }

    // لجلب تاريخ الدفعات عند تثبيت التطبيق في هاتف جديد
    public function getPayments(Request $request) {
        return \App\Models\CustomerPayment::whereHas('customer', function($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->get();
    }


}