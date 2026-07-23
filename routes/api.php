<?php

use App\Http\Controllers\ApplePayController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\GooglePlayController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MyFatoorahController;
use App\Http\Controllers\OwnerWithdrawalController;
use App\Http\Controllers\PartnershipController;
use App\Http\Controllers\PaymentInvoiceController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SaaSController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\WebhookController;
use App\Saas\Models\AppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// مسارات عامة
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:5,1');
Route::post('/verify-email-registration', [AuthController::class, 'verifyEmailRegistration']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::get('/ping', function () {
    return response()->json(['status' => 'online']);
});

Route::get('/plans', [SaaSController::class, 'getPlans']);
Route::get('/announcements/active', [SaaSController::class, 'getActiveAnnouncement']);
Route::post('/promo-codes/validate', [SaaSController::class, 'validatePromoCode']);

Route::get('/support/faqs', [SupportController::class, 'faqs']);
Route::get('/support/instructions', [SupportController::class, 'instructions']);

Route::get('/app-status', function () {
    return response()->json([
        'min_version' => AppConfig::where('key', 'min_version')->value('value') ?? '1.0.0',
        'is_disabled' => AppConfig::where('key', 'is_disabled')->value('value') === 'true',
        'update_url' => AppConfig::where('key', 'update_url')->value('value') ?? 'https://bhasbni.com',
        'whatsapp_number' => AppConfig::where('key', 'whatsapp_number')->value('value') ?? '',
    ]);
});
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);
Route::post('/webhooks/apple', [ApplePayController::class, 'webhook']);
Route::get('/webhooks/myfatoorah/callback', [MyFatoorahController::class, 'callback']);

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// مسارات محمية لجميع المستخدمين (مدير وكاشير)
Route::middleware(['auth:sanctum', 'plan'])->group(function () {

    // 🚀 المسارات التي تم إخراجها لتعمل مع الكاشير والمدير بشكل سليم
    Route::get('/sync/delta', [SyncController::class, 'delta']);
    Route::post('/fcm-token', [FcmController::class, 'updateToken']);

    Route::get('/support/tickets', [SupportController::class, 'myTickets']);
    Route::post('/support/tickets', [SupportController::class, 'createTicket'])->middleware('throttle:3,1');

    Route::post('/pay/myfatoorah', [MyFatoorahController::class, 'checkout']);
    Route::get('/my-subscription', [SaaSController::class, 'mySubscription']);
    Route::get('/payments/{payment}/invoice.pdf', [PaymentInvoiceController::class, 'download'])
        ->middleware('throttle:10,1');
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/inventory/movements', [InventoryController::class, 'index']);
    Route::post('/inventory/movements/sync', [InventoryController::class, 'syncMovements']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/partnership/pull', [PartnershipController::class, 'pull']);
    Route::post('/partnership/partner', [PartnershipController::class, 'syncPartner']);
    Route::put('/partnership/partner/{id}', [PartnershipController::class, 'updatePartner']);
    Route::delete('/partnership/partner/{id}', [PartnershipController::class, 'deletePartner']);
    Route::post('/partnership/good', [PartnershipController::class, 'syncGood']);
    Route::put('/partnership/good/{id}', [PartnershipController::class, 'updateGood']);
    Route::delete('/partnership/good/{id}', [PartnershipController::class, 'deleteGood']);
    Route::post('/partnership/record', [PartnershipController::class, 'syncRecord']);
    Route::delete('/partnership/record-item/{id}', [PartnershipController::class, 'deleteRecordItem']);

    Route::get('/profiles', [ProfileController::class, 'show']);
    Route::post('/profiles', [ProfileController::class, 'update']);
    Route::post('/rpc/set_manager_password', [ProfileController::class, 'setManagerPassword']);
    Route::post('/rpc/verify_manager_password', [ProfileController::class, 'verifyManagerPassword']);
    Route::post('/rpc/is_manager_password_set', [ProfileController::class, 'isManagerPasswordSet']);

    Route::post('/customers/{id}/payments', [CustomerController::class, 'storePayment']);
    Route::get('/customer_payments', [CustomerController::class, 'getPayments']);

    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('expense_categories', ExpenseCategoryController::class);
    Route::apiResource('product_categories', ProductCategoryController::class);

    Route::post('/rpc/get_sale_details', fn (Request $r) => app(SaleController::class)->show($r, $r->p_sale_id));
    Route::post('/rpc/process_return', [SaleController::class, 'processReturn']);
    Route::post('/rpc/process_exchange', [SaleController::class, 'processExchange']);

    Route::apiResource('products', ProductController::class)->only(['index']);
    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/rpc/create_sale_and_update_inventory', [SaleController::class, 'store'])->middleware('throttle:financial_operations');
    Route::apiResource('customers', CustomerController::class);

    Route::apiResource('suppliers', SupplierController::class);
    Route::post('/suppliers/{id}/payments', [SupplierController::class, 'storePayment']);
    Route::get('/supplier_payments', [SupplierController::class, 'getPayments']);

    Route::post('/cash/sync', [CashController::class, 'sync'])->middleware('throttle:financial_operations');
    Route::get('/cash/drawers', [CashController::class, 'getDrawers']);

    Route::post('/verify-google-play', [GooglePlayController::class, 'verifyPurchase']);
    Route::post('/verify-apple-purchase', [ApplePayController::class, 'validateReceipt']);

    // 🚀 مسارات حصرية للمدير فقط 🚀
    Route::middleware(['manager'])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::apiResource('products', ProductController::class)->except(['index']);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('owner_withdrawals', OwnerWithdrawalController::class);
        Route::post('/rpc/get_financial_summary', [ReportsController::class, 'summary']);
    });
});
