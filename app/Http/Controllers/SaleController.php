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
            $saleTotalInCurrency = 0; 
            $totalProfitInUsd = 0;   
            $saleItemsData = [];
            
            $rateToUsd = $request->p_rate_to_usd_at_sale ?? 1.0;

            foreach ($request->p_sale_items_data as $itemData) {
                $product = $user->products()->lockForUpdate()->find($itemData['product_id']);
                if (!$product) throw new \Exception("Product not found");
                if ($product->quantity < $itemData['quantity']) throw new \Exception("Insufficient stock");

                $product->decrement('quantity', $itemData['quantity']);

                $qty = $itemData['quantity'];
                $unitPriceUsd = $itemData['price']; 
                $unitPriceLocal = $unitPriceUsd * $rateToUsd;
                $profit = ($unitPriceUsd - $product->cost_price) * $qty;

                $saleTotalInCurrency += ($unitPriceLocal * $qty);
                $totalProfitInUsd += $profit;

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_sold' => $qty,
                    'price_at_sale' => $unitPriceLocal,
                    'cost_price_at_sale' => $product->cost_price,
                ];
            }

            // --- حسابات الخصم والضريبة والدين ---
            $discount = $request->p_discount_amount ?? 0;
            $taxPercentage = $request->p_tax_percentage ?? 0;
            
            $finalTotalLocal = $saleTotalInCurrency - $discount;
            $taxAmount = $finalTotalLocal * ($taxPercentage / 100);
            $finalTotalLocal += $taxAmount;

            $paidAmount = $request->p_paid_amount ?? $finalTotalLocal;
            
            $paymentStatus = 'paid';
            if ($paidAmount == 0) {
                $paymentStatus = 'unpaid';
            } elseif ($paidAmount < $finalTotalLocal) {
                $paymentStatus = 'partial';
            }

            // إنشاء الفاتورة
            $sale = $user->sales()->create([
                'employee_id' => $request->p_employee_id,
                'customer_id' => $request->p_customer_id, // 👈 العميل
                'total_price' => $finalTotalLocal,
                'total_profit' => $totalProfitInUsd - ($discount / $rateToUsd), // تقليل الربح بقيمة الخصم بالدولار
                'currency_code' => $request->p_currency_code,
                'rate_to_usd_at_sale' => $rateToUsd,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus,
            ]);

            $sale->items()->createMany($saleItemsData);

            // 👈 تسجيل الدين على العميل إذا لم يدفع المبلغ كاملاً
            if ($request->p_customer_id && $paidAmount < $finalTotalLocal) {
                $debt = $finalTotalLocal - $paidAmount;
                $user->customers()->where('id', $request->p_customer_id)->increment('balance', $debt);
            }

            return response()->json(['id' => $sale->id]);
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
            
            // Refund Amount is in Local Currency
            $refundAmountLocal = $saleItem->price_at_sale * $request->p_return_quantity;
            
            // Profit Reduction
            $rate = $sale->rate_to_usd_at_sale ?: 1;
            $unitPriceUsd = $saleItem->price_at_sale / $rate;
            $profitReductionUsd = ($unitPriceUsd - $saleItem->cost_price_at_sale) * $request->p_return_quantity;

            $sale->decrement('total_price', $refundAmountLocal);
            $sale->decrement('total_profit', $profitReductionUsd);

            // 🚨 معالجة الديون (Refund to Customer Balance if unpaid) 🚨
            if ($sale->customer_id) {
                $customer = $sale->customer;
                // إذا كان الفاتورة ديناً (unpaid أو partial)، إرجاع البضاعة يقلل من دين العميل
                // أما إذا كان دافع كاش، فيجب إعطاؤه كاش (لن نغير رصيده في الديون).
                // للتبسيط في الـ POS: إرجاع البضاعة لعميل مسجل يعتبر دائماً تقليلاً لحسابه الإجمالي إذا كان مديناً.
                if ($customer->balance > 0) {
                    $reduceAmount = min($customer->balance, $refundAmountLocal);
                    $customer->decrement('balance', $reduceAmount);
                }
            }
            
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