<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('expires_at');
            // وضعیت اشتراک: فعال، منقضی شده، تعلیق شده
            $table->enum('status', ['active', 'expired', 'suspended'])->default('active');

            $table->text('description')->nullable();
            $table->timestamps();

            // ایندکس برای جستجوی سریع‌تر
            $table->index(['customer_id', 'status']);
            $table->index(['end_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
