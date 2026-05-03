<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // فهرسة سريعة للبحث بناءً على مبادئ AIS
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 50); // created, updated, deleted, login
            $table->nullableMorphs('auditable'); // auditable_type, auditable_id
            
            // تخزين الفروقات فقط بحجم خفيف جداً
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // سياق العملية (الرقابة الداخلية)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // إضافة فهارس لتسريع استرجاع البيانات بدون استهلاك المعالج
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};