<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * قالب‌های پیامک نسخه ۲:
 *  - قالب‌های اختصاصی «مدیر» (اطلاع‌رسانی خرید/درخواست اشتراک به مدیر)
 *  - تأیید ثبت درخواست رایگان به مشتری
 *  - اطلاع رد درخواست اشتراک به مشتری
 *
 * updateOrInsert → اجرای دوباره امن است و روی نصب‌های موجود فقط رکوردهای
 * جدید را اضافه می‌کند (قالب‌های قبلی دست‌نخورده می‌مانند).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $templates = [
            // ---------- قالب‌های مدیر ----------
            [
                'key'        => 'admin_purchase_paid',
                'title'      => 'مدیر: پرداخت موفق خرید پکیج',
                'body'       => "📥 پرداخت موفق\nمشتری: {customer_name}\nپکیج: {package_name}\nمبلغ: {amount} تومان\n{site_name}",
                'variables'  => ['customer_name', 'package_name', 'amount', 'site_name'],
            ],
            [
                'key'        => 'admin_subscription_request',
                'title'      => 'مدیر: درخواست اشتراک جدید',
                'body'       => "📥 درخواست اشتراک جدید\nمشتری: {customer_name}\nطرح: {plan_name}\nمبلغ: {amount} تومان\nبرای تأیید به پنل مراجعه کنید.\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'amount', 'site_name'],
            ],
            [
                'key'        => 'admin_subscription_paid',
                'title'      => 'مدیر: پرداخت اشتراک',
                'body'       => "📥 پرداخت اشتراک\nمشتری: {customer_name}\nطرح: {plan_name}\nمبلغ: {amount} تومان\nدر انتظار تأیید شما.\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'amount', 'site_name'],
            ],

            // ---------- قالب‌های مشتری ----------
            [
                'key'        => 'subscription_request_ack',
                'title'      => 'ثبت درخواست اشتراک (رایگان)',
                'body'       => "{customer_name} عزیز\nدرخواست شما برای طرح «{plan_name}» ثبت شد و در انتظار تأیید مدیر است.\nپس از تأیید، اشتراک شما فعال می‌شود.\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'site_name'],
            ],
            [
                'key'        => 'subscription_rejected',
                'title'      => 'رد درخواست اشتراک',
                'body'       => "{customer_name} عزیز\nمتأسفانه درخواست اشتراک «{plan_name}» شما تأیید نشد.\n{reason}\n{site_name}",
                'variables'  => ['customer_name', 'plan_name', 'reason', 'site_name'],
            ],
        ];

        foreach ($templates as $tpl) {
            DB::table('sms_templates')->updateOrInsert(
                ['key' => $tpl['key']],
                [
                    'title'         => $tpl['title'],
                    'body'          => $tpl['body'],
                    'variables'     => json_encode($tpl['variables']),
                    'pattern_codes' => json_encode((object) []),
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('sms_templates')
            ->whereIn('key', ['admin_purchase_paid', 'admin_subscription_request', 'admin_subscription_paid', 'subscription_request_ack', 'subscription_rejected'])
            ->delete();
    }
};
