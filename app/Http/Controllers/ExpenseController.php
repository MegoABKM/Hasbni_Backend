<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

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
        // FIX: Validate and handle nullable category
        $validated = $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'expense_date' => 'required|date',
            // 'nullable' allows sending null. 'exists' checks if the ID is valid.
            'category_id' => 'nullable|integer|exists:expense_categories,id',
            'recurrence' => 'nullable|string',
        ]);

        return $request->user()->expenses()->create($validated);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric',
            'amount_in_currency' => 'nullable|numeric',
            'currency_code' => 'nullable|string|max:3',
            'expense_date' => 'sometimes|date',
            'category_id' => 'nullable|integer|exists:expense_categories,id',
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