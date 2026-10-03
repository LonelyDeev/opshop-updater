<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جداول پیامک: قالب‌ها (پترن‌ها) + لاگ ارسال
 * قالب‌های پیش‌فرض هم در همین مایگریشن ساخته می‌شوند تا روی هاست با
 * یک بار migrate کامل نصب شوند.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- sms_templates ----------
        if (!Schema::hasTable('sms_templates')) {
            Schema::create('sms_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();          // purchase_paid, subscription_activated, …
                $table->string('title');                       // نام فارسی
                $table->text('body');                          // متن با متغیرهای {var}
                $table->json('variables')->nullable();         // لیست متغیرهای مجاز
                $table->json('pattern_codes')->nullable();     // {kavenegar: "...", melipayamak: "...", ...}
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ---------- sms_logs ----------
        if (!Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->string('mobile', 20)->index();
                $table->string('template_key', 64)->nullable()->index();
                $table->string('driver', 32)->nullable();
                $table->text('message')->nullable();           // متن نهایی رندرشده
                $table->string('pattern_code')->nullable();    // کد پترن استفاده‌شده (در صورت ارسال پترنی)
                $table->string('status', 16)->default('failed'); // sent | failed | skipped
                $table->text('response')->nullable();          // پاسخ خام سرویس
                $table->text('error')->nullable();             // پیام خطا
                $table->nullableMorphs('loggable');            // purchase/subscription مرتبط
                $table->timestamps();
            });
        }

        // ---------- قالب‌های پیش‌فرض ----------
        $now = now();
        $defaults = [
            [
                'key'        => 'purchase_paid',
                'title'      => 'پرداخت موفق خرید پکیج',
                'body'       => "{customer_name} عزیز\nپرداخت مبلغ {amount} تومان برای پکیج «{package_name}» با موفقیت انجام شد.\nکد لایسنس شما: {license_key}\n{site_name}",
                'variables'  => ['customer_name', 'package_name', 'amount', 'license_key', 'site_name'],
                'pattern_codes' => (object) [],
            ],
            [
                'key'        => 'subscription_paid',
                'title'      => 'پرداخت اشتراک (در انتظار تأیید)',
                'body'       => "{customer_name} عزیز\nپرداخت شما برای اشتراک «{plan_name}» به مبلغ {amount} تومان دریافت شد.\nپس از تأیید مدیر، اشتراک شما فعال می‌شود.\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'amount', 'site_name'],
                'pattern_codes' => (object) [],
            ],
            [
                'key'        => 'subscription_activated',
                'title'      => 'فعال‌شدن اشتراک',
                'body'       => "{customer_name} عزیز\nاشتراک «{plan_name}» شما فعال شد و تا تاریخ {end_date} معتبر است.\nپکیج‌های همراه این طرح برای شما رایگان‌اند.\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'end_date', 'site_name'],
                'pattern_codes' => (object) [],
            ],
            [
                'key'        => 'subscription_renewed',
                'title'      => 'تمدید اشتراک / دسترسی رایگان',
                'body'       => "{customer_name} عزیز\nدسترسی رایگان شما به پکیج «{package_name}» در اشتراک «{plan_name}» تا تاریخ {end_date} تمدید شد.\n{site_name}",
                'variables'  => ['customer_name', 'package_name', 'plan_name', 'end_date', 'site_name'],
                'pattern_codes' => (object) [],
            ],
            [
                'key'        => 'subscription_expiring',
                'title'      => 'اشتراک در حال انقضا',
                'body'       => "{customer_name} عزیز\nاشتراک «{plan_name}» شما تا {days_left} روز دیگر (تاریخ {end_date}) منقضی می‌شود.\nبرای تمدید، از همین سایت اقدام کنید.\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'days_left', 'end_date', 'site_name'],
                'pattern_codes' => (object) [],
            ],
            [
                'key'        => 'subscription_expired',
                'title'      => 'انقضای اشتراک',
                'body'       => "{customer_name} عزیز\nاشتراک «{plan_name}» شما در تاریخ {end_date} منقضی شد.\nبا تمدید طرح، پکیج‌های رایگان دوباره فعال می‌شوند.\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'end_date', 'site_name'],
                'pattern_codes' => (object) [],
            ],
        ];

        foreach ($defaults as $tpl) {
            \Illuminate\Support\Facades\DB::table('sms_templates')->updateOrInsert(
                ['key' => $tpl['key']],
                [
                    'title'         => $tpl['title'],
                    'body'          => $tpl['body'],
                    'variables'     => json_encode($tpl['variables']),
                    'pattern_codes' => json_encode($tpl['pattern_codes']),
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );
        }

        // ---------- تنظیمات پیش‌فرض پیامک ----------
        $settings = [
            ['sms_enabled', '0', 'sms'],
            ['sms_driver', 'kavenegar', 'sms'],
            ['sms_admin_mobile', '', 'sms'],
            ['sms_notify_admin', '0', 'sms'],
            ['sms_expire_days', '3', 'sms'],
            // kavenegar
            ['sms_kavenegar_apikey', '', 'sms'],
            // melipayamak
            ['sms_melipayamak_username', '', 'sms'],
            ['sms_melipayamak_password', '', 'sms'],
            ['sms_melipayamak_from', '', 'sms'],
            // ippanel
            ['sms_ippanel_username', '', 'sms'],
            ['sms_ippanel_password', '', 'sms'],
            ['sms_ippanel_from', '', 'sms'],
            // farazsms (iranpayamak)
            ['sms_farazsms_apikey', '', 'sms'],
            ['sms_farazsms_from', '', 'sms'],
            // idehpardazan
            ['sms_idehpardazan_apikey', '', 'sms'],
            ['sms_idehpardazan_secretkey', '', 'sms'],
        ];

        foreach ($settings as [$key, $value, $group]) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'type' => 'string', 'group' => $group, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_templates');

        \Illuminate\Support\Facades\DB::table('settings')
            ->where('group', 'sms')
            ->whereIn('key', [
                'sms_enabled', 'sms_driver', 'sms_admin_mobile', 'sms_notify_admin', 'sms_expire_days',
                'sms_kavenegar_apikey',
                'sms_melipayamak_username', 'sms_melipayamak_password', 'sms_melipayamak_from',
                'sms_ippanel_username', 'sms_ippanel_password', 'sms_ippanel_from',
                'sms_farazsms_apikey', 'sms_farazsms_from',
                'sms_idehpardazan_apikey', 'sms_idehpardazan_secretkey',
            ])
            ->delete();
    }
};
