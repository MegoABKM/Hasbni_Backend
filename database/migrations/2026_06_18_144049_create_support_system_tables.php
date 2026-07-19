<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🚀 تنظيف الجداول إن كانت موجودة مسبقاً لتفادي خطأ Table already exists 🚀
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('instructions');
        Schema::dropIfExists('faqs');

        // 1. جدول الأسئلة الشائعة
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. جدول تعليمات الاستخدام
        Schema::create('instructions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. جدول تذاكر الدعم الفني
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->text('admin_reply')->nullable();
            $table->enum('status', ['open', 'answered', 'closed'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('instructions');
        Schema::dropIfExists('faqs');
    }
};