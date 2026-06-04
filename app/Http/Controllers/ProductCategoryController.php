<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(Request $request) {
        return $request->user()->productCategories;
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $existing = $request->user()->productCategories()->where('name', $validated['name'])->first();
        if ($existing) return $existing;

        return $request->user()->productCategories()->create($validated);
    }
    
    public function destroy(Request $request, $id) {
         $request->user()->productCategories()->findOrFail($id)->delete();
         return response()->json(['success'=>true]);
    }
}
