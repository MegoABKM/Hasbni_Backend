<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Events\ShopDataUpdated;

class OwnerWithdrawalController extends Controller {
    public function index(Request $request) {
        return $request->user()->withdrawals()->latest('withdrawal_date')->get();
    }

    public function store(Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'withdrawal_date' => 'required|date',
            'created_at' => 'nullable|date',
        ]);
        
        $clientCreatedAt = $request->created_at ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i:s') : null;
        if ($clientCreatedAt) {
            $existing = $user->withdrawals()
                ->where('description', $validated['description'])
                ->where('created_at', $clientCreatedAt)
                ->first();
            if ($existing) return $existing;
            $validated['created_at'] = $clientCreatedAt;
            $validated['updated_at'] = $clientCreatedAt;
        }

        $withdrawal = $user->withdrawals()->create($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'withdrawal_created', $withdrawal->toArray(), $request->header('X-Device-ID')));
        }

        return $withdrawal;
    }

    public function update(Request $request, $id) {
        $user = $request->user();
        $validated = $request->validate([
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'withdrawal_date' => 'required|date',
        ]);
        
        $user->withdrawals()->findOrFail($id)->update($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'withdrawal_updated', [], $request->header('X-Device-ID')));
        }

        return response()->json(['success'=>true]);
    }

    public function destroy(Request $request, $id) {
        $user = $request->user();
        $user->withdrawals()->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'withdrawal_deleted', ['id' => $id], $request->header('X-Device-ID')));
        }

        return response()->json(['success'=>true]);
    }
}