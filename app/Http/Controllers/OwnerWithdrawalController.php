<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class OwnerWithdrawalController extends Controller {
    public function index(Request $request) {
        return $request->user()->withdrawals()->latest('withdrawal_date')->get();
    }

    public function store(Request $request) {
        // FIX: Validate numeric amount and valid date
        $validated = $request->validate([
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'withdrawal_date' => 'required|date',
        ]);
        
        return $request->user()->withdrawals()->create($validated);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'withdrawal_date' => 'required|date',
        ]);
        
        $request->user()->withdrawals()->findOrFail($id)->update($validated);
        return response()->json(['success'=>true]);
    }

    public function destroy(Request $request, $id) {
        $request->user()->withdrawals()->findOrFail($id)->delete();
        return response()->json(['success'=>true]);
    }
}