<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; 
use App\Events\ShopDataUpdated;

class ExpenseController extends Controller
{
    public function index(Request $request) {
        return $request->user()->expenses()
            ->with('category:id,name')
            ->latest('expense_date')
            ->get()
            ->map(function($expense) {
                $expense->category = ['name' => $expense->category->name ?? ''];
                return $expense;
            });
    }

    public function store(Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'expense_date' => 'required|date',
            'created_at' => 'nullable|date',
            'category_id' => [
                'nullable', 'integer',
                Rule::exists('expense_categories', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'recurrence' => 'nullable|string',
        ]);

        $clientCreatedAt = $request->created_at ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i:s') : null;
        if ($clientCreatedAt) {
            $existing = $user->expenses()
                ->where('description', $validated['description'])
                ->where('created_at', $clientCreatedAt)
                ->first();
            if ($existing) return $existing;
            $validated['created_at'] = $clientCreatedAt;
            $validated['updated_at'] = $clientCreatedAt;
        }

        $expense = $user->expenses()->create($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'expense_created', $expense->toArray(), $request->header('X-Device-ID')));
        }

        return $expense;
    }

    public function update(Request $request, $id) {
        $user = $request->user();
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'expense_date' => 'sometimes|date',
            'category_id' => [
                'nullable', 'integer',
                Rule::exists('expense_categories', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'recurrence' => 'nullable|string',
        ]);

        $user->expenses()->findOrFail($id)->update($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'expense_updated', [], $request->header('X-Device-ID')));
        }

        return response()->json(['message' => 'Updated']);
    }

    public function destroy(Request $request, $id) {
        $user = $request->user();
        $user->expenses()->findOrFail($id)->delete();

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'expense_deleted', ['id' => $id], $request->header('X-Device-ID')));
        }

        return response()->json(['message' => 'Deleted']);
    }
}