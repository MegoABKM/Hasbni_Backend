<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request) {
        return $request->user()->expenseCategories;
    }

    public function store(Request $request) {
        // FIX: Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        return $request->user()->expenseCategories()->create($validated);
    }
    
    // Note: You should probably add destroy/update here eventually to complete sync logic
    public function destroy(Request $request, $id) {
         $request->user()->expenseCategories()->findOrFail($id)->delete();
         return response()->json(['success'=>true]);
    }
}