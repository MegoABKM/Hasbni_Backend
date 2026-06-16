<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index(Request $request) {
        return $request->user()->productCategories;
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $existing = $request->user()->productCategories()->where('name', $validated['name'])->first();
        if ($existing) {
            $existing->update([
                'icon' => $validated['icon'] ?? $existing->icon,
                'color' => $validated['color'] ?? $existing->color,
            ]);
            return $existing;
        }

        $validated['user_id'] = $request->user()->id;
        return ProductCategory::create($validated);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $category = $request->user()->productCategories()->findOrFail($id);
        $category->update($validated);
        
        return response()->json($category);
    }
    
    public function destroy(Request $request, $id) {
         $request->user()->productCategories()->findOrFail($id)->delete();
         return response()->json(['success'=>true]);
    }
}