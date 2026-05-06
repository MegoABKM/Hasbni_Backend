<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('id');
            }
        });
    }
    public function down(): void {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }
        });
    }
};