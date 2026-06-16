<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */public function up()
{
    Schema::table('products', function (Blueprint $table) {
        // نضع القيمة الافتراضية مساوية للـ cost_price في حال كانت البيانات قديمة
        $table->decimal('last_purchase_price', 10, 2)->nullable()->after('cost_price');
    });
}

public function down()
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn('last_purchase_price');
    });
}
};
