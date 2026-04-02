<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request) {
        $query = $request->user()->products();
        
        if ($request->has('searchQuery') && $request->searchQuery) {
            $q = $request->searchQuery;
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhere('barcode', 'like', "%$q%");
            });
        }

        $sort = $request->sortBy ?? 'name';
        $dir = $request->ascending == 'true' ? 'asc' : 'desc';
        
        return $query->orderBy($sort, $dir)->paginate($request->limit ?? 20);
    }

    public function store(Request $request) {
        // 1. Validate Data (Prevents 500 Errors)
        $validated = $request->validate([
            'name' => 'required|string',
            'barcode' => 'nullable|string', // Allows null barcodes
            'quantity' => 'required|integer',
            'cost_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
        ]);

        // 2. Create Product safely
        return $request->user()->products()->create($validated);
    }

    public function update(Request $request, $id) {
        // 1. Find Product belonging to user
        $product = $request->user()->products()->findOrFail($id);

        // 2. Validate Data
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'barcode' => 'nullable|string',
            'quantity' => 'sometimes|integer',
            'cost_price' => 'sometimes|numeric',
            'selling_price' => 'sometimes|numeric',
        ]);

        // 3. Update
        $product->update($validated);
        return $product;
    }

    public function destroy(Request $request, $id) {
        $request->user()->products()->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}