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
        $validated = $request->validate([
            'name' => 'required|string',
            'barcode' => 'nullable|string',
            'quantity' => 'required|integer',
            'alert_threshold' => 'required|integer',
            'cost_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'last_purchase_price' => 'nullable|numeric',
            'partner_id' => 'nullable|integer',
            'product_category_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer', // 🚀 Added
            'created_at' => 'nullable|date',
        ]);

        $clientCreatedAt = $request->created_at ? \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i:s') : null;

        if ($clientCreatedAt) {
            $existing = $request->user()->products()
                ->where('name', $validated['name'])
                ->where('created_at', $clientCreatedAt)
                ->first();
            if ($existing) {
                return $existing;
            }
            $validated['created_at'] = $clientCreatedAt;
            $validated['updated_at'] = $clientCreatedAt;
        }

        return $request->user()->products()->create($validated);
    }

    public function update(Request $request, $id) {
        $product = $request->user()->products()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'barcode' => 'nullable|string',
            'quantity' => 'sometimes|integer',
            'alert_threshold' => 'sometimes|integer|min:1',
            'cost_price' => 'sometimes|numeric',
            'selling_price' => 'sometimes|numeric',
            'last_purchase_price' => 'nullable|numeric',
            'partner_id' => 'nullable|integer',
            'product_category_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer', // 🚀 Added
        ]);

        $product->update($validated);
        return $product;
    }

    public function destroy(Request $request, $id) {
        $request->user()->products()->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}