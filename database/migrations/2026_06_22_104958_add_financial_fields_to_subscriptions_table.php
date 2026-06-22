<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // اگر ستون‌ها قبلاً نیستند اضافه شوند
            if (!Schema::hasColumn('subscriptions', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('project_id'); // قیمت واحد
            }
            if (!Schema::hasColumn('subscriptions', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0)->after('price'); // مبلغ تخفیف
            }
            if (!Schema::hasColumn('subscriptions', 'final_amount')) {
                $table->decimal('final_amount', 15, 2)->default(0)->after('discount'); // مبلغ نهایی (محاسباتی یا ذخیره شده)
            }
            if (!Schema::hasColumn('subscriptions', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->after('final_amount'); // وضعیت پرداخت
            }
            if (!Schema::hasColumn('subscriptions', 'description')) {
                $table->text('description')->nullable()->after('payment_status'); // توضیحات
            }
            if (!Schema::hasColumn('subscriptions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('status'); // تاریخ انقضا
            }
        });

        if (!Schema::hasColumn('customers', 'update_code')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('update_code')->unique()->after('website_url');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['price', 'discount', 'final_amount', 'payment_status', 'description']);
        });
    }
};
