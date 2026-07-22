<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'shop_owner')->update(['role' => 'tenant']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('tenant')->change();
            $table->index('role');
            $table->index('country');
            $table->index('is_banned');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->index(['user_id', 'status']);
            $table->index(['plan_id', 'status']);
            $table->index(['status', 'ends_at']);
            $table->index(['status', 'starts_at']);
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->index('is_active');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['user_id', 'status']);
            $table->index(['subscription_id', 'status']);
            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['subscription_id', 'status']);
            $table->dropIndex(['status', 'paid_at']);
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->dropIndex(['is_active']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['plan_id', 'status']);
            $table->dropIndex(['status', 'ends_at']);
            $table->dropIndex(['status', 'starts_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['country']);
            $table->dropIndex(['is_banned']);
            $table->string('role')->default('shop_owner')->change();
        });

        DB::table('users')->where('role', 'tenant')->update(['role' => 'shop_owner']);
    }
};
