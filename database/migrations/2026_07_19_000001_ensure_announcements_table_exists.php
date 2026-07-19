<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('message');
                $table->string('type')->default('info');
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'title')) {
                $table->string('title')->nullable();
            }

            if (! Schema::hasColumn('announcements', 'message')) {
                $table->text('message')->nullable();
            }

            if (! Schema::hasColumn('announcements', 'type')) {
                $table->string('type')->default('info');
            }

            if (! Schema::hasColumn('announcements', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('announcements', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }

            if (! Schema::hasColumn('announcements', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('announcements', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive repair migration. The original promo/announcement
        // migration owns table removal for fresh rollbacks.
    }
};
