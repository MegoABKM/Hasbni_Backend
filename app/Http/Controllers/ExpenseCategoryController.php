<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request) {
        return $request->user()->expenseCategories;
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Anti-Duplication
        $existing = $request->user()->expenseCategories()->where('name', $validated['name'])->first();
        if ($existing) return $existing;

        return $request->user()->expenseCategories()->create($validated);
    }
    
    public function destroy(Request $request, $id) {
         $request->user()->expenseCategories()->findOrFail($id)->delete();
         return response()->json(['success'=>true]);
    }
}