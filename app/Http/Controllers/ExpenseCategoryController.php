<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Events\ShopDataUpdated;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request) {
        return $request->user()->expenseCategories;
    }

    public function store(Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $existing = $user->expenseCategories()->where('name', $validated['name'])->first();
        if ($existing) return $existing;

        $category = $user->expenseCategories()->create($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'expense_category_created'));
        }

        return $category;
    }
    
    public function destroy(Request $request, $id) {
         $user = $request->user();
         $user->expenseCategories()->findOrFail($id)->delete();

         if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'expense_category_deleted'));
         }

         return response()->json(['success'=>true]);
    }
}