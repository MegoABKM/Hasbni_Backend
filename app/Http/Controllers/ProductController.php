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
            'alert_threshold' => 'required|integer', // 👈 يجب أن يكون موجوداً هنا
            'cost_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
              'last_purchase_price' => 'nullable|numeric',
                 'partner_id' => 'nullable|integer',
        ]);

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
               'alert_threshold' => 'sometimes|integer|min:1',
            'cost_price' => 'sometimes|numeric',
            'selling_price' => 'sometimes|numeric',
              'last_purchase_price' => 'nullable|numeric',
             'partner_id' => 'nullable|integer',
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