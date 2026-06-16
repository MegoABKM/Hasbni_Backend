<?php
namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request) {
        return $request->user()->sales()->with('items')
            ->latest()
            ->paginate($request->limit ?? 300);
    }

    public function show(Request $request, $id) {
        return $request->user()->sales()->with('items')->findOrFail($id);
    }

    public function store(Request $request) {
        $saleId = $this->executeStore($request->user(), $request->all());
        return response()->json(['id' => $saleId]);
    }

    public function processReturn(Request $request) {
        $request->validate([
            'p_return_quantity' => 'required|integer|min:1'
        ]);
        
        $isVoid = $request->p_is_void ?? false;
        $deductionAmount = $request->p_deduction_amount ?? null;

        // 🚨 التعديل الجذري: لا نتجاهل الطلب إذا كان ناقصاً، بل نعيد خطأ ليحتفظ به التطبيق ويحاول لاحقاً
        if ($request->has('p_sale_item_id') && $request->p_sale_item_id != null) {
            $this->executeReturn($request->user(), $request->p_sale_item_id, $request->p_return_quantity, $isVoid, $deductionAmount);
            return response()->json(true);
        } elseif ($request->has('p_sale_id') && $request->p_sale_id != null && $request->has('p_product_id') && $request->p_product_id != null) {
            $this->executeReturnBySaleAndProduct($request->user(), $request->p_sale_id, $request->p_product_id, $request->p_return_quantity, $isVoid, $deductionAmount);
            return response()->json(true);
        }
        
        // إذا فشل في إيجاد المعرفات، نرفض الطلب لكي لا يضيع من طابور المزامنة في الهاتف
        return response()->json(['message' => 'Missing server IDs. Will retry later.'], 400);
    }

    public function processExchange(Request $request) {
        return DB::transaction(function () use ($request) {
            $user = $request->user();
            
            $this->executeReturn($user, $request->p_sale_item_id_to_return, $request->p_return_quantity, false, null);
            
            $returnedItem = \App\Models\SaleItem::find($request->p_sale_item_id_to_return);
            $returnedValueLocal = $returnedItem ? ($returnedItem->price_at_sale * $request->p_return_quantity) : 0;

            $newSaleId = $this->executeStore($user, [
                'p_sale_items_data' => $request->p_new_sale_items_data,
                'p_currency_code' => $request->p_currency_code,
                'p_rate_to_usd_at_sale' => $request->p_rate_to_usd_at_sale,
                'p_employee_id' => $request->p_employee_id,
                'p_customer_id' => $request->p_customer_id ?? null,
                'p_discount_amount' => $request->p_discount_amount ?? 0,
                'p_tax_amount' => $request->p_tax_amount ?? 0, 
                'p_paid_amount' => $request->p_paid_amount ?? null,
                'p_tendered_amount' => $request->p_tendered_amount ?? 0,
                'p_tendered_currency' => $request->p_tendered_currency ?? null,
                'p_change_amount' => $request->p_change_amount ?? 0,
                'p_change_currency' => $request->p_change_currency ?? null,
                'p_total_profit' => $request->p_total_profit ?? 0, 
                'p_total_price' => $request->p_total_price ?? 0, 
                'p_payment_status' => $request->p_payment_status ?? 'paid',
                'p_created_at' => clone now(),
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

   private function executeStore($user, $data) {
        return DB::transaction(function () use ($user, $data) {
            
            $clientCreatedAt = null;
            if (isset($data['p_created_at'])) {
                $clientCreatedAt = \Carbon\Carbon::parse($data['p_created_at'])->format('Y-m-d H:i:s');
                $existingSale = $user->sales()->where('created_at', $clientCreatedAt)->first();
                if ($existingSale) {
                    return $existingSale->id; 
                }
            }

            $saleItemsData = [];
            foreach ($data['p_sale_items_data'] as $itemData) {
                $product = $user->products()->lockForUpdate()->find($itemData['product_id']);
                if (!$product) continue; 

                $returnedQty = $itemData['returned_quantity'] ?? 0;
                $netQty = $itemData['quantity'] - $returnedQty;

                $newQty = $product->quantity - $netQty;
                $product->quantity = $newQty < 0 ? 0 : $newQty;
                $product->save();

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_sold' => $itemData['quantity'],
                    'returned_quantity' => $returnedQty, 
                    'price_at_sale' => $itemData['price'], 
                    'cost_price_at_sale' => $product->cost_price,
                ];
            }

            // 🚨 التعديل هنا: إضافة invoice_number و has_returns للحفظ في السيرفر 🚨
            $saleData = [
                'employee_id' => $data['p_employee_id'] ?? null,
                'customer_id' => $data['p_customer_id'] ?? null,
                'invoice_number' => $data['p_invoice_number'] ?? null, // 👈
                'total_price' => $data['p_total_price'] ?? 0, 
                'total_profit' => $data['p_total_profit'] ?? 0, 
                'currency_code' => $data['p_currency_code'],
                'rate_to_usd_at_sale' => $data['p_rate_to_usd_at_sale'] ?? 1.0,
                'discount_amount' => $data['p_discount_amount'] ?? 0,
                'tax_amount' => $data['p_tax_amount'] ?? 0, 
                'paid_amount' => $data['p_paid_amount'] ?? 0,
                'payment_status' => $data['p_payment_status'] ?? 'paid',
                'tendered_amount' => $data['p_tendered_amount'] ?? 0,
                'tendered_currency' => $data['p_tendered_currency'] ?? null,
                'change_amount' => $data['p_change_amount'] ?? 0,
                'change_currency' => $data['p_change_currency'] ?? null,
                'has_returns' => $data['p_has_returns'] ?? false, // 👈
            ];

            if ($clientCreatedAt) {
                $saleData['created_at'] = $clientCreatedAt;
                $saleData['updated_at'] = $clientCreatedAt;
            }

            $sale = $user->sales()->create($saleData);
            $sale->items()->createMany($saleItemsData);

            return $sale->id;
        });
    }

    private function executeReturn($user, $saleItemId, $returnQty, $isVoid, $deductionAmount) {
        return DB::transaction(function () use ($user, $saleItemId, $returnQty, $isVoid, $deductionAmount) {
            $saleItem = \App\Models\SaleItem::whereHas('sale', function($q) use ($user){
                $q->where('user_id', $user->id);
            })->where('id', $saleItemId)->first();

            if (!$saleItem) return true; 

            $this->applyReturnLogic($saleItem, $returnQty, $isVoid, $deductionAmount);
            return true;
        });
    }

    private function executeReturnBySaleAndProduct($user, $saleId, $productId, $returnQty, $isVoid, $deductionAmount) {
        return DB::transaction(function () use ($user, $saleId, $productId, $returnQty, $isVoid, $deductionAmount) {
            // 🚨 إصلاح للـ SQL Ambiguous Column بجعلها صريحة sale_id
            $saleItem = \App\Models\SaleItem::where('sale_id', $saleId)
                ->where('product_id', $productId)
                ->whereHas('sale', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->first();

            if (!$saleItem) return true; 

            $this->applyReturnLogic($saleItem, $returnQty, $isVoid, $deductionAmount);
            return true;
        });
    }

   private function applyReturnLogic($saleItem, $returnQty, $isVoid = false, $deductionAmount = null) {
        $availableToReturn = $saleItem->quantity_sold - $saleItem->returned_quantity;
        if ($returnQty > $availableToReturn) {
            $returnQty = $availableToReturn;
        }
        
        if ($returnQty <= 0) return;
        
        $saleItem->increment('returned_quantity', $returnQty);
        
        if ($saleItem->product_id) {
            Product::where('id', $saleItem->product_id)->increment('quantity', $returnQty);
        }
        
        $sale = $saleItem->sale;
        
        if ($deductionAmount !== null) {
            $sale->total_price -= $deductionAmount;
        } else {
            $refundAmountLocal = $saleItem->price_at_sale * $returnQty;
            $sale->total_price -= $refundAmountLocal;
        }

        $rate = $sale->rate_to_usd_at_sale ?: 1;
        $profitReductionUsd = (($saleItem->price_at_sale / $rate) - $saleItem->cost_price_at_sale) * $returnQty;

        $sale->total_profit -= $profitReductionUsd;
        $sale->has_returns = true; // 👈 🚀 إضافة هذه العلامة للسيرفر 🚀
        
        if ($isVoid || $sale->total_price <= 0.01) { 
            $sale->total_price = 0;
            $sale->total_profit = 0;
            $sale->discount_amount = 0;
            $sale->tax_amount = 0;
            $sale->payment_status = 'voided';
        }
        
        $sale->save();
    }
}