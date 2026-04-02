<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Profiles (Extends User)
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shop_name')->default('My Shop');
            $table->string('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('city')->nullable();
            $table->string('manager_password')->nullable(); // Hashed
            $table->timestamps();
        });

        // 2. Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->timestamps();
        });

        // 3. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('barcode')->nullable()->index();
            $table->integer('quantity')->default(0);
            $table->decimal('cost_price', 15, 3);
            $table->decimal('selling_price', 15, 3);
            $table->timestamps();
        });

        // 4. Expense Categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        // 5. Exchange Rates
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('currency_code');
            $table->decimal('rate_to_usd', 15, 6);
            $table->unique(['user_id', 'currency_code']);
            $table->timestamps();
        });

        // 6. Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->text('description');
            $table->decimal('amount', 15, 3); // Normalized to USD
            $table->decimal('amount_in_currency', 15, 3); // Original currency amount
            $table->string('currency_code')->default('USD');
            $table->decimal('rate_to_usd_at_expense', 15, 6)->default(1);
            $table->string('recurrence')->default('one_time');
            $table->timestamp('expense_date');
            $table->timestamps();
        });

        // 7. Owner Withdrawals
        Schema::create('owner_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 3); // Normalized USD
            $table->decimal('amount_in_currency', 15, 3);
            $table->string('currency_code')->default('USD');
            $table->decimal('rate_to_usd_at_withdrawal', 15, 6)->default(1);
            $table->timestamp('withdrawal_date');
            $table->timestamps();
        });

        // 8. Sales
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('total_price', 15, 3);
            $table->decimal('total_profit', 15, 3);
            $table->string('currency_code')->default('USD');
            $table->decimal('rate_to_usd_at_sale', 15, 6)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Sale Items
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name'); // Snapshot
            $table->integer('quantity_sold');
            $table->integer('returned_quantity')->default(0);
            $table->decimal('price_at_sale', 15, 3);
            $table->decimal('cost_price_at_sale', 15, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('owner_withdrawals');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('profiles');
    }
};