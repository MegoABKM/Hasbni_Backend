<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class OwnerWithdrawalController extends Controller {
    public function index(Request $request) {
        return $request->user()->withdrawals()->latest('withdrawal_date')->get();
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'withdrawal_date' => 'required|date',
            'created_at' => 'nullable|date',
        ]);
        
        // 🚨 Server-Side Deduplication
        $clientCreatedAt = $request->created_at ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i:s') : null;
        if ($clientCreatedAt) {
            $existing = $request->user()->withdrawals()
                ->where('description', $validated['description'])
                ->where('created_at', $clientCreatedAt)
                ->first();
            if ($existing) return $existing;
            $validated['created_at'] = $clientCreatedAt;
            $validated['updated_at'] = $clientCreatedAt;
        }

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