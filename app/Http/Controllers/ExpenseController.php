<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; 

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
        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'expense_date' => 'required|date',
            'created_at' => 'nullable|date',
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_categories', 'id')->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                }),
            ],
            'recurrence' => 'nullable|string',
        ]);

        // 🚨 Server-Side Deduplication
        $clientCreatedAt = $request->created_at ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i:s') : null;
        if ($clientCreatedAt) {
            $existing = $request->user()->expenses()
                ->where('description', $validated['description'])
                ->where('created_at', $clientCreatedAt)
                ->first();
            if ($existing) return $existing;
            $validated['created_at'] = $clientCreatedAt;
            $validated['updated_at'] = $clientCreatedAt;
        }

        return $request->user()->expenses()->create($validated);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'expense_date' => 'sometimes|date',
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_categories', 'id')->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                }),
            ],
            'recurrence' => 'nullable|string',
        ]);

        $request->user()->expenses()->findOrFail($id)->update($validated);
        return response()->json(['message' => 'Updated']);
    }

    public function destroy(Request $request, $id) {
        $request->user()->expenses()->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}