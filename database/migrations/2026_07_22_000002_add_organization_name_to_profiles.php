<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            $table->string('organization_name')->nullable()->after('shop_name');
        });

        DB::table('profiles')
            ->whereNull('organization_name')
            ->update(['organization_name' => DB::raw('shop_name')]);
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            $table->dropColumn('organization_name');
        });
    }
};
