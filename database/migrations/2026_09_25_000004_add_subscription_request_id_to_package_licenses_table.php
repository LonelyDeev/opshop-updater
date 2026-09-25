<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اتصال لایسنس‌ها به درخواست اشتراک:
 * لایسنس‌هایی که از طریق تأیید درخواست اشتراک صادر/تمدید شده‌اند
 * (مثل purchase_id برای خرید پکیج).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_licenses', function (Blueprint $table) {
            $table->foreignId('subscription_request_id')
                ->nullable()
                ->after('purchase_id')
                ->constrained('subscription_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('package_licenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_request_id');
        });
    }
};
