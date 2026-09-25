<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درخواست‌های خرید/فعال‌سازی طرح اشتراک:
 *
 * مشتری در فرانت (یا از طریق API فروشگاه) طرحی را انتخاب و می‌خرد →
 * یک رکورد درخواست ساخته می‌شود (payment_status + status) → پس از پرداخت موفق،
 * وضعیت «در انتظار تأیید مدیر» می‌شود → مدیر از پنل تأیید/رد می‌کند →
 * در صورت تأیید، برای همه پکیج‌های طرح لایسنس صادر/تمدید می‌شود (فعال‌سازی).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();

            $table->string('gateway', 32)->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('transaction_id', 64)->nullable()->index();
            $table->text('payment_url')->nullable();
            // برای خریدهای API: فروشگاه بیرونی پس از بازگشت از درگاه به این آدرس برمی‌گردد
            $table->text('callback_url')->nullable();

            // pending | paid | failed | free (free = طرح رایگان، نیازی به پرداخت ندارد)
            $table->string('payment_status', 20)->default('pending');
            // pending (در انتظار تأیید مدیر) | approved (تأیید و فعال‌سازی شد) | rejected (رد شد)
            $table->string('status', 20)->default('pending');

            $table->text('admin_note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // خروجی فعال‌سازی: لایسنس‌های صادرشده و سایر متادیتا
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_requests');
    }
};
