<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طرح‌های اشتراک (Subscription Plans)
 *
 * طرح‌های پولی/رایگان با مدت‌زمان مشخص (مثلاً ۱ ماهه، ۳ ماهه، یک‌ساله یا نامحدود)،
 * قابلیت‌های طرح (features به‌صورت JSON آرایه‌ای از متن)، محدودیت یک‌بار مصرف و
 * فهرست پکیج‌هایIncluded (جدول subscription_plan_package).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            // مدت اعتبار پیش‌فرض طرح (ماه) — ۰ = نامحدود
            $table->unsignedSmallInteger('duration_months')->default(0);
            // قیمت به تومان — برای طرح‌های رایگان is_free=true و قیمت صفر
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('discount_price')->nullable();
            $table->boolean('is_free')->default(false);
            // محدودیت استفاده یک‌باره: هر مشتری فقط یک‌بار می‌تواند از این طرح استفاده کند
            $table->boolean('is_one_time')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            // قابلیت‌های طرح: ["دسترسی به همه پکیج‌ها", ...]
            $table->json('features')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
