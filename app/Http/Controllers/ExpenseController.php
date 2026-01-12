<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request) {
        // Fetch expenses with category name
        return $request->user()->expenses()
            ->with('category:id,name')
            ->latest('expense_date')
            ->get()
            ->map(function($expense) {
                // Formatting for frontend to match existing model
                $expense->category = ['name' => $expense->category->name ?? ''];
                return $expense;
            });
    }

    public function store(Request $request) {
        return $request->user()->expenses()->create($request->all());
    }

    public function update(Request $request, $id) {
        $request->user()->expenses()->findOrFail($id)->update($request->all());
        return response()->json(['message' => 'Updated']);
    }

    public function destroy(Request $request, $id) {
        $request->user()->expenses()->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}