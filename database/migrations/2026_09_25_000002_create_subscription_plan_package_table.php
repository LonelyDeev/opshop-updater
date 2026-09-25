<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پکیج‌های هر طرح اشتراک:
 * فهرست پکیج‌هایی که با خرید/فعال‌سازی طرح، مشتری به آن‌ها دسترسی (لایسنس) می‌گیرد.
 * duration_months اختیاری = مدت دسترسی اختصاصیِ همان پکیج در این طرح؛
 * null یعنی همان مدت پیش‌فرضِ طرح استفاده شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            // null = مدت پیش‌فرض طرح؛ ۰ = نامحدود؛ عدد = تعداد ماه اختصاصی
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'package_id'], 'sub_plan_package_unique');
            $table->index('package_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_package');
    }
};
