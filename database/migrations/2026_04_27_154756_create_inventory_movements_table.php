<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type'); // 'sale_out', 'restock_in', 'return_in', 'manual_adjust', 'initial_stock'
            $table->integer('quantity_change');
            $table->integer('current_balance');
            $table->decimal('cost_price_at_time', 15, 2)->default(0);
            $table->integer('reference_id')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_movements');
    }
};