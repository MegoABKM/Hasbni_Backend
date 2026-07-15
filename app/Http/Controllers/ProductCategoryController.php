<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Events\ShopDataUpdated;

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

        $user = $request->user();
        $existing = $user->productCategories()->where('name', $validated['name'])->first();
        
        if ($existing) {
            $existing->update([
                'icon' => $validated['icon'] ?? $existing->icon,
                'color' => $validated['color'] ?? $existing->color,
            ]);
            
            if ($user->hasRealtimeSyncFeature()) {
                event(new ShopDataUpdated($user->id, 'product_category_updated', $existing->toArray(), $request->header('X-Device-ID')));
            }
            return $existing;
        }

        $validated['user_id'] = $user->id;
        $category = ProductCategory::create($validated);

        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'product_category_created', $category->toArray(), $request->header('X-Device-ID')));
        }

        return $category;
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $category = $user->productCategories()->findOrFail($id);
        $category->update($validated);
        
        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'product_category_updated', $category->toArray(), $request->header('X-Device-ID')));
        }

        return response()->json($category);
    }
    
    public function destroy(Request $request, $id) {
        $user = $request->user();
        $user->productCategories()->findOrFail($id)->delete();
        
        if ($user->hasRealtimeSyncFeature()) {
            event(new ShopDataUpdated($user->id, 'product_category_deleted', ['id' => $id], $request->header('X-Device-ID')));
        }

        return response()->json(['success'=>true]);
    }
}