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
        return $request->user()->products()->create($request->all());
    }

    public function update(Request $request, $id) {
        $product = $request->user()->products()->findOrFail($id);
        $product->update($request->all());
        return $product;
    }

    public function destroy(Request $request, $id) {
        $request->user()->products()->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}