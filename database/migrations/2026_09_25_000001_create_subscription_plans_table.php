<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طرح‌های اشتراک (Subscription Plans)
 *
 * طرحی که مشتری از فروشگاه می‌خرد (پولی یا رایگان) و پس از تأیید مدیر فعال می‌شود.
 * هر طرح: مدت اعتبار + قیمت + قابلیت‌ها (features JSON) + لیست پکیج‌های همراه
 * (رابطه many-to-many با فیلد free_months = مدت دسترسی رایگان به هر پکیج).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                      // نام طرح (مثلاً «اشتراک حرفه‌ای»)
            $table->string('slug', 120)->unique();            // شناسه یکتا برای API/فرانت
            $table->text('description')->nullable();          // توضیحات طرح
            $table->unsignedSmallInteger('duration_months')->default(1); // مدت اعتبار اشتراک (ماه) — ۰ = نامحدود
            $table->unsignedBigInteger('price')->default(0);           // قیمت (تومان) — ۰ = رایگان
            $table->unsignedBigInteger('discount_price')->nullable();  // مبلغ تخفیف
            $table->boolean('is_one_time')->default(false);   // مشتری فقط یک‌بار می‌تواند از این طرح استفاده کند
            $table->boolean('is_active')->default(true);      // فعال/غیرفعال در فروشگاه
            $table->integer('sort_order')->default(0);        // ترتیب نمایش در فرانت
            $table->json('features')->nullable();             // قابلیت‌های طرح: ["پشتیبانی رایگان", …]
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // پکیج‌های همراه هر طرح: با خرید طرح، این پکیج‌ها به‌مدت free_months رایگان می‌شوند
        Schema::create('subscription_plan_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->unsignedSmallInteger('free_months')->default(1); // مدت دسترسی رایگان به این پکیج (ماه) — ۰ = نامحدود
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'package_id']);
        });

        // سفارش‌های اشتراک (خرید طرح از فروشگاه/API + پرداخت + وضعیت تأیید مدیر)
        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedBigInteger('amount')->default(0);          // قیمت طرح زمان ثبت سفارش
            $table->unsignedBigInteger('discount')->default(0);        // تخفیف
            $table->unsignedBigInteger('final_amount')->default(0);    // مبلغ نهایی پرداختی
            $table->string('gateway', 40)->nullable();                // درگاه پرداخت
            $table->string('transaction_id')->nullable()->index();     // شناسه تراکنش درگاه
            $table->string('payment_url')->nullable();                 // آدرس پرداخت
            $table->string('callback_url')->nullable();                // آدرس بازگشت فروشگاه مشتری (API)
            $table->string('status', 20)->default('pending');          // وضعیت پرداخت: pending/paid/failed
            $table->string('admin_status', 20)->default('pending');    // وضعیت تأیید مدیر: pending/approved/rejected
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete(); // اشتراک صادرشده پس از تأیید
            $table->timestamp('starts_at')->nullable();                // شروع اعتبار (پس از تأیید)
            $table->timestamp('expires_at')->nullable();               // پایان اعتبار (پس از تأیید)
            $table->json('meta')->nullable();                          // snapshot طرح هنگام ثبت + فرم درگاه
            $table->timestamps();

            $table->index(['customer_id', 'admin_status']);
            $table->index(['subscription_plan_id', 'admin_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
        Schema::dropIfExists('subscription_plan_package');
        Schema::dropIfExists('subscription_plans');
    }
};
