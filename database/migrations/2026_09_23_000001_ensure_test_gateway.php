<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * درگاه «تست» — درگاه داخلی برای توسعه.
 *
 * بدون کلید و بدون پیکربندی؛ در صفحه شبیه‌ساز آن (payment/form) دکمه‌های
 * «پرداخت موفق / پرداخت ناموفق» دقیقاً همان کاری را انجام می‌دهند که
 * درگاه واقعی انجام می‌دهد (تأیید پرداخت + صدور لایسنس / مسیر ناموفق).
 *
 * این مایگریشن:
 *   ۱) ردیف gateways با key=local را می‌سازد یا به‌روزرسانی می‌کند (نام «تست»، فعال).
 *   ۲) مقادیر پیکربندی قدیمی شبیه‌ساز (اگر از نسخه‌های قبلی موجود باشند) را
 *      پاک می‌کند تا متن‌ها و دکمه‌های جدید به‌عنوان پیش‌فرض اعمال شوند.
 *
 * برای خاموش‌کردن درگاه تست کافی است در /admin/settings/gateways کلید فعال را
 * روی خاموش بگذارید (درگاه واقعی را که فعال کردید، این را خاموش کنید).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $id = DB::table('gateways')->where('key', 'local')->value('id');

        $payload = [
            'name'        => 'تست',
            'description' => 'درگاه داخلی توسعه — بدون کلید (دکمه‌های پرداخت موفق/ناموفق)',
            'is_active'   => true,
            'ordering'    => 99,
            'updated_at'  => $now,
        ];

        if ($id) {
            DB::table('gateways')->where('id', $id)->update($payload);
        } else {
            $payload['key'] = 'local';
            $payload['created_at'] = $now;
            $id = DB::table('gateways')->insertGetId($payload);
        }

        // حذف مقادیر قدیمی شبیه‌ساز (عنوان/دکمه‌ها) تا پیش‌فرض‌های جدید اعمال شود
        DB::table('gateway_configs')
            ->where('gateway_id', $id)
            ->whereIn('key', ['title', 'description', 'orderLabel', 'amountLabel', 'payButton', 'cancelButton'])
            ->delete();
    }

    public function down(): void
    {
        // درگاه تست را غیرفعال می‌کنیم (ردیف را حذف نمی‌کنیم تا تنظیمات دستی حفظ شود)
        DB::table('gateways')->where('key', 'local')->update([
            'is_active'  => false,
            'updated_at' => now(),
        ]);
    }
};
