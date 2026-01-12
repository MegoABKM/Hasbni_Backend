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

// Public Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // --- THIS WAS MISSING ---
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // ------------------------

    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile (RPC replacements)
    Route::get('/profiles', [ProfileController::class, 'show']); 
    Route::post('/profiles', [ProfileController::class, 'update']);
    Route::post('/rpc/set_manager_password', [ProfileController::class, 'setManagerPassword']);
    Route::post('/rpc/verify_manager_password', [ProfileController::class, 'verifyManagerPassword']);
    Route::post('/rpc/is_manager_password_set', [ProfileController::class, 'isManagerPasswordSet']);

    // Products
    Route::apiResource('products', ProductController::class);

    // Employees
    Route::apiResource('employees', EmployeeController::class);

    // Expenses & Categories
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('expense_categories', ExpenseCategoryController::class);

    // Withdrawals
    Route::apiResource('owner_withdrawals', OwnerWithdrawalController::class);

    // Sales (RPC replacements)
    Route::post('/rpc/create_sale_and_update_inventory', [SaleController::class, 'store']);
    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/rpc/get_sale_details', fn(Request $r) => app(SaleController::class)->show($r, $r->p_sale_id));
    Route::post('/rpc/process_return', [SaleController::class, 'processReturn']);
    Route::post('/rpc/process_exchange', [SaleController::class, 'processExchange']);

    // Reports
    Route::post('/rpc/get_financial_summary', [ReportsController::class, 'summary']);
});