<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_type', 20)
                ->default('tenant')
                ->after('role')
                ->index();
        });

        DB::table('users')
            ->whereIn('role', ['super_admin', 'support_admin', 'finance_admin'])
            ->update(['account_type' => 'staff']);

        DB::table('users')
            ->where('role', 'tenant')
            ->update(['account_type' => 'tenant']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['account_type']);
            $table->dropColumn('account_type');
        });
    }
};
