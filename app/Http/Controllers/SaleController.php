<?php
namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        
        return DB::transaction(function () use ($request, $user) {
            $saleTotalInCurrency = 0; // The total to show on the receipt (e.g. SYP)
            $totalProfitInUsd = 0;    // The real profit in USD
            $saleItemsData = [];
            
            // 1. Get the rate (e.g., 12000 for SYP)
            // If currency is USD, rate is 1.
            $rateToUsd = $request->p_rate_to_usd_at_sale ?? 1.0;

            foreach ($request->p_sale_items_data as $itemData) {
                $product = $user->products()->lockForUpdate()->find($itemData['product_id']);
                
                if (!$product) throw new \Exception("Product not found");
                if ($product->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                // 2. Deduct Inventory
                $product->decrement('quantity', $itemData['quantity']);

                $qty = $itemData['quantity'];
                
                // 3. Price Logic
                // Frontend sends price in USD (e.g. 30)
                $unitPriceUsd = $itemData['price']; 
                
                // Calculate Price in Local Currency for the Invoice (e.g. 30 * 12000 = 360,000)
                $unitPriceLocal = $unitPriceUsd * $rateToUsd;

                // 4. Profit Logic (Keep everything in USD)
                // Profit = (Selling Price USD - Cost Price USD) * Qty
                // Example: (30 - 20) * 1 = 10 USD Profit
                $profit = ($unitPriceUsd - $product->cost_price) * $qty;

                // Accumulate Totals
                $saleTotalInCurrency += ($unitPriceLocal * $qty);
                $totalProfitInUsd += $profit;

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_sold' => $qty,
                    'price_at_sale' => $unitPriceLocal, // Save 360,000 (Local)
                    'cost_price_at_sale' => $product->cost_price, // Save 20 (USD)
                ];
            }

            // 5. Create Sale Record
            $sale = $user->sales()->create([
                'employee_id' => $request->p_employee_id,
                'total_price' => $saleTotalInCurrency, // e.g. 360,000 SYP
                'total_profit' => $totalProfitInUsd,   // e.g. 10 USD
                'currency_code' => $request->p_currency_code,
                'rate_to_usd_at_sale' => $rateToUsd,
            ]);

            $sale->items()->createMany($saleItemsData);

            return $sale->id;
        });
    }

    public function index(Request $request) {
        return $request->user()->sales()
            ->select('id', 'total_price', 'currency_code', 'created_at')
            ->latest()
            ->paginate($request->limit ?? 20);
    }

    public function show(Request $request, $id) {
        return $request->user()->sales()->with('items')->findOrFail($id);
    }

    public function processReturn(Request $request) {
        $request->validate([
            'p_sale_item_id' => 'required',
            'p_return_quantity' => 'required|integer|min:1'
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            
            $saleItem = \App\Models\SaleItem::whereHas('sale', function($q) use ($user){
                $q->where('user_id', $user->id);
            })->where('id', $request->p_sale_item_id)->firstOrFail();

            if ($request->p_return_quantity > ($saleItem->quantity_sold - $saleItem->returned_quantity)) {
                throw new \Exception("Invalid return quantity");
            }

            // Update Sale Item
            $saleItem->increment('returned_quantity', $request->p_return_quantity);

            // Restore Inventory
            if ($saleItem->product_id) {
                Product::where('id', $saleItem->product_id)->increment('quantity', $request->p_return_quantity);
            }

            $sale = $saleItem->sale;
            
            // Refund Amount is in Local Currency (e.g. 360,000)
            $refundAmountLocal = $saleItem->price_at_sale * $request->p_return_quantity;
            
            // Profit Reduction must be calculated back to USD
            // Reverse the rate: LocalPrice / Rate = USD Price
            $rate = $sale->rate_to_usd_at_sale ?: 1;
            $unitPriceUsd = $saleItem->price_at_sale / $rate;
            
            $profitReductionUsd = ($unitPriceUsd - $saleItem->cost_price_at_sale) * $request->p_return_quantity;

            $sale->decrement('total_price', $refundAmountLocal);
            $sale->decrement('total_profit', $profitReductionUsd);
            
            return true;
        });
    }

    public function processExchange(Request $request) {
        return DB::transaction(function () use ($request) {
            // 1. Process Return
            $this->processReturn(new Request([
                'p_sale_item_id' => $request->p_sale_item_id_to_return,
                'p_return_quantity' => $request->p_return_quantity
            ]));
            
            // Get value of returned item (Local Currency)
            $returnedItem = \App\Models\SaleItem::find($request->p_sale_item_id_to_return);
            $returnedValueLocal = $returnedItem->price_at_sale * $request->p_return_quantity;

            // 2. Process New Sale
            $newSaleId = $this->store(new Request([
                'p_sale_items_data' => $request->p_new_sale_items_data,
                'p_currency_code' => $request->p_currency_code,
                'p_rate_to_usd_at_sale' => $request->p_rate_to_usd_at_sale,
                'p_employee_id' => $request->p_employee_id,
            ]));

            $newSale = Sale::find($newSaleId);
            // Difference in Local Currency to show on screen
            $priceDiff = $newSale->total_price - $returnedValueLocal; 

            return [
                'new_sale_id' => $newSaleId,
                'price_difference' => $priceDiff,
                'currency_code' => $request->p_currency_code
            ];
        });
    }
}