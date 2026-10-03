<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ستون logo برای gateways:
 * مسیر نسبی لوگوی اختصاصی هر درگاه (آپلودی مدیر).
 * اگر خالی باشد → لوگوی پیش‌فرض public/uploads/gateways/logos/{key}.svg استفاده می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('gateways', 'logo')) {
            Schema::table('gateways', function (Blueprint $table) {
                $table->string('logo', 512)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gateways', 'logo')) {
            Schema::table('gateways', function (Blueprint $table) {
                $table->dropColumn('logo');
            });
        }
    }
};
