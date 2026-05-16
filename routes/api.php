<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\OwnerWithdrawalController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaaSController;

// ==========================================
// 🔓 Public Routes (الروابط المتاحة للجميع)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/ping', function () {
    return response()->json(['status' => 'online']);
});

Route::get('/plans', [SaaSController::class, 'getPlans']);

// 🚀 تم نقل هذا المسار إلى هنا ليتمكن التطبيق من قراءته قبل تسجيل الدخول 🚀
// مسار: routes/api.php
Route::get('/app-status', function() {
    return response()->json([
        'min_version' => \App\Models\AppConfig::where('key', 'min_version')->value('value') ?? '1.0.0',
        'is_disabled' => \App\Models\AppConfig::where('key', 'is_disabled')->value('value') === 'true',
        'update_url' => \App\Models\AppConfig::where('key', 'update_url')->value('value') ?? 'https://bhasbni.com',
        // 🚀 السطر الجديد: جلب رقم الواتساب
        'whatsapp_number' => \App\Models\AppConfig::where('key', 'whatsapp_number')->value('value') ?? '', 
    ]);
});
Route::post('/webhooks/stripe', [\App\Http\Controllers\WebhookController::class, 'handleStripe']);
// ==========================================
// 🔒 Protected Routes (الروابط المحمية بالتوكن)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/my-subscription', [SaaSController::class, 'mySubscription']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/inventory/movements', [\App\Http\Controllers\InventoryController::class, 'index']);
    Route::post('/inventory/movements/sync', [\App\Http\Controllers\InventoryController::class, 'syncMovements']);
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Partnership Routes
    Route::get('/partnership/pull', [\App\Http\Controllers\PartnershipController::class, 'pull']);
    Route::post('/partnership/partner', [\App\Http\Controllers\PartnershipController::class, 'syncPartner']);
    Route::put('/partnership/partner/{id}', [\App\Http\Controllers\PartnershipController::class, 'updatePartner']);
    Route::delete('/partnership/partner/{id}', [\App\Http\Controllers\PartnershipController::class, 'deletePartner']);
    Route::post('/partnership/good', [\App\Http\Controllers\PartnershipController::class, 'syncGood']);
    Route::put('/partnership/good/{id}', [\App\Http\Controllers\PartnershipController::class, 'updateGood']);
    Route::delete('/partnership/good/{id}', [\App\Http\Controllers\PartnershipController::class, 'deleteGood']);
    Route::post('/partnership/record', [\App\Http\Controllers\PartnershipController::class, 'syncRecord']);
    Route::delete('/partnership/record-item/{id}', [\App\Http\Controllers\PartnershipController::class, 'deleteRecordItem']);
    
    // Profile
    Route::get('/profiles', [ProfileController::class, 'show']); 
    Route::post('/profiles', [ProfileController::class, 'update']);
    Route::post('/rpc/set_manager_password', [ProfileController::class, 'setManagerPassword']);
    Route::post('/rpc/verify_manager_password', [ProfileController::class, 'verifyManagerPassword']);
    Route::post('/rpc/is_manager_password_set', [ProfileController::class, 'isManagerPasswordSet']);

    Route::post('/customers/{id}/payments', [CustomerController::class, 'storePayment']);
    Route::get('/customer_payments', [CustomerController::class, 'getPayments']);

    // Employees & Expenses
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('expense_categories', ExpenseCategoryController::class);

    // Sales RPC
    Route::post('/rpc/get_sale_details', fn(Request $r) => app(SaleController::class)->show($r, $r->p_sale_id));
    Route::post('/rpc/process_return', [SaleController::class, 'processReturn']);
    Route::post('/rpc/process_exchange', [SaleController::class, 'processExchange']);
    
    // POS Core
    Route::apiResource('products', ProductController::class)->only(['index']);
    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/rpc/create_sale_and_update_inventory', [SaleController::class, 'store']);
    Route::apiResource('customers', CustomerController::class);
    
    // Suppliers
    Route::apiResource('suppliers', App\Http\Controllers\SupplierController::class);
    Route::post('/suppliers/{id}/payments', [App\Http\Controllers\SupplierController::class, 'storePayment']);
    Route::get('/supplier_payments', [App\Http\Controllers\SupplierController::class, 'getPayments']);
    
    // 🚨 الروابط المحمية (للمدير فقط) 🚨
    Route::middleware(['manager'])->group(function () {
        Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index']);
        Route::apiResource('products', ProductController::class)->except(['index']);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('owner_withdrawals', OwnerWithdrawalController::class);
        Route::post('/rpc/get_financial_summary', [ReportsController::class, 'summary']);
    });
    
    Route::post('/cash/sync', [\App\Http\Controllers\CashController::class, 'sync']);
    Route::get('/cash/drawers', [\App\Http\Controllers\CashController::class, 'getDrawers']);
    
    Route::post('/verify-google-play', [\App\Http\Controllers\GooglePlayController::class, 'verifyPurchase']);
});