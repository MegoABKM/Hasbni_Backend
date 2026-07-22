<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_daily_metrics', function (Blueprint $table): void {
            $table->decimal('new_mrr', 14, 2)->default(0)->after('mrr');
            $table->decimal('expansion_mrr', 14, 2)->default(0)->after('new_mrr');
            $table->decimal('contraction_mrr', 14, 2)->default(0)->after('expansion_mrr');
            $table->decimal('churned_mrr', 14, 2)->default(0)->after('contraction_mrr');
        });
    }

    public function down(): void
    {
        Schema::table('saas_daily_metrics', function (Blueprint $table): void {
            $table->dropColumn([
                'new_mrr',
                'expansion_mrr',
                'contraction_mrr',
                'churned_mrr',
            ]);
        });
    }
};
