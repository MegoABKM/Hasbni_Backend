<?php
namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    // 1. جلب المبيعات
    public function index(Request $request) {
        return $request->user()->sales()
            ->latest()
            ->paginate($request->limit ?? 20);
    }

    // 2. جلب تفاصيل فاتورة معينة
    public function show(Request $request, $id) {
        return $request->user()->sales()->with('items')->findOrFail($id);
    }

    // 3. إنشاء بيع جديد (يستدعي الدالة الخاصة)
    public function store(Request $request) {
        $saleId = $this->executeStore($request->user(), $request->all());
        return response()->json(['id' => $saleId]);
    }

    // 4. إرجاع منتج (يستدعي الدالة الخاصة)
    public function processReturn(Request $request) {
        $request->validate([
            'p_sale_item_id' => 'required',
            'p_return_quantity' => 'required|integer|min:1'
        ]);
        
        $this->executeReturn($request->user(), $request->p_sale_item_id, $request->p_return_quantity);
        return response()->json(true);
    }

    // 5. الاستبدال (يجمع بين الإرجاع والبيع الجديد)
    public function processExchange(Request $request) {
        return DB::transaction(function () use ($request) {
            $user = $request->user();
            
            // أ. تنفيذ الإرجاع
            $this->executeReturn($user, $request->p_sale_item_id_to_return, $request->p_return_quantity);
            
            $returnedItem = \App\Models\SaleItem::find($request->p_sale_item_id_to_return);
            $returnedValueLocal = $returnedItem->price_at_sale * $request->p_return_quantity;

            // ب. إنشاء بيع جديد
            $newSaleId = $this->executeStore($user, [
                'p_sale_items_data' => $request->p_new_sale_items_data,
                'p_currency_code' => $request->p_currency_code,
                'p_rate_to_usd_at_sale' => $request->p_rate_to_usd_at_sale,
                'p_employee_id' => $request->p_employee_id,
                'p_customer_id' => $request->p_customer_id ?? null,
                'p_discount_amount' => $request->p_discount_amount ?? 0,
                'p_tax_percentage' => $request->p_tax_percentage ?? 0,
                'p_paid_amount' => $request->p_paid_amount ?? null,
            ]);

            $newSale = Sale::find($newSaleId);
            $priceDiff = $newSale->total_price - $returnedValueLocal; 

            return response()->json([
                'new_sale_id' => $newSaleId,
                'price_difference' => $priceDiff,
                'currency_code' => $request->p_currency_code
            ]);
        });
    }

    // =========================================================================
    // دوال المساعدة (Private Logic) لتنفيذ العمليات بأمان داخل قاعدة البيانات
    // =========================================================================

    private function executeStore($user, $data) {
        return DB::transaction(function () use ($user, $data) {
            $saleTotalInCurrency = 0; 
            $totalProfitInUsd = 0;   
            $saleItemsData = [];
            $rateToUsd = $data['p_rate_to_usd_at_sale'] ?? 1.0;

            foreach ($data['p_sale_items_data'] as $itemData) {
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

            $discount = $data['p_discount_amount'] ?? 0;
            $taxPercentage = $data['p_tax_percentage'] ?? 0;
            
            $finalTotalLocal = $saleTotalInCurrency - $discount;
            $taxAmount = $finalTotalLocal * ($taxPercentage / 100);
            $finalTotalLocal += $taxAmount;

            $paidAmount = $data['p_paid_amount'] ?? $finalTotalLocal;
            
            $paymentStatus = 'paid';
            if ($paidAmount == 0) {
                $paymentStatus = 'unpaid';
            } elseif ($paidAmount < $finalTotalLocal) {
                $paymentStatus = 'partial';
            }

            $sale = $user->sales()->create([
                'employee_id' => $data['p_employee_id'] ?? null,
                'customer_id' => $data['p_customer_id'] ?? null,
                'total_price' => $finalTotalLocal,
                'total_profit' => $totalProfitInUsd - ($discount / $rateToUsd), 
                'currency_code' => $data['p_currency_code'],
                'rate_to_usd_at_sale' => $rateToUsd,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus,
            ]);

            $sale->items()->createMany($saleItemsData);

            return $sale->id; // إرجاع رقم الفاتورة
        });
    }

    private function executeReturn($user, $saleItemId, $returnQty) {
        return DB::transaction(function () use ($user, $saleItemId, $returnQty) {
            $saleItem = \App\Models\SaleItem::whereHas('sale', function($q) use ($user){
                $q->where('user_id', $user->id);
            })->where('id', $saleItemId)->firstOrFail();

            if ($returnQty > ($saleItem->quantity_sold - $saleItem->returned_quantity)) {
                throw new \Exception("Invalid return quantity");
            }
            
            $saleItem->increment('returned_quantity', $returnQty);
            
            if ($saleItem->product_id) {
                Product::where('id', $saleItem->product_id)->increment('quantity', $returnQty);
            }
            
            $sale = $saleItem->sale;
            $refundAmountLocal = $saleItem->price_at_sale * $returnQty;
            $rate = $sale->rate_to_usd_at_sale ?: 1;
            $profitReductionUsd = (($saleItem->price_at_sale / $rate) - $saleItem->cost_price_at_sale) * $returnQty;

            $sale->decrement('total_price', $refundAmountLocal);
            $sale->decrement('total_profit', $profitReductionUsd);
            
            return true;
        });
    }
}