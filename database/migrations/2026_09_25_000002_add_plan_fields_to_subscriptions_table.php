<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اتصال اشتراک‌های موجود به طرح‌های اشتراک (subscription_plans):
 *  - subscription_plan_id: اشتراکِ صادرشده از طریق خرید یک طرح
 *  - subscription_order_id: سفارشی که منجر به این اشتراک شد
 *  - project_id و تاریخ‌ها nullable می‌شوند (اشتراکِ طرح‌محور پروژه خاصی ندارد
 *    و مدت نامحدود باید بتواند null باشد)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('project_id')
                ->constrained('subscription_plans')->nullOnDelete();
            $table->unsignedBigInteger('subscription_order_id')->nullable()->after('subscription_plan_id');

            $table->foreignId('project_id')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->date('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('expires_at')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->date('start_date')->nullable(false)->change();
            $table->foreignId('project_id')->nullable(false)->change();

            $table->dropColumn(['subscription_order_id']);
            $table->dropConstrainedForeignId('subscription_plan_id');
        });
    }
};
