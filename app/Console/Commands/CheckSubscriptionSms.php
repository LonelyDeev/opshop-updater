<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Sms\SmsManager;
use Illuminate\Console\Command;

/**
 * بررسی روزانه اشتراک‌ها:
 *  - نزدیک به انقضا (N روز مانده — قابل تنظیم: sms_expire_days)
 *  - تازه منقضی‌شده (دیروز)
 *
 * اجرای دستی:  php artisan sms:check-subscriptions
 * زمان‌بندی:  هر روز ساعت ۹ (routes/console.php)
 */
class CheckSubscriptionSms extends Command
{
    protected $signature = 'sms:check-subscriptions {--dry : فقط نمایش، بدون ارسال}';

    protected $description = 'ارسال پیامک اشتراک‌های نزدیک به انقضا و تازه منقضی‌شده';

    public function handle(SmsManager $sms): int
    {
        $enabled = filter_var(setting('sms_enabled', '0'), FILTER_VALIDATE_BOOLEAN);

        if (!$enabled) {
            $this->info('سیستم پیامک غیرفعال است؛ خروج.');

            return self::SUCCESS;
        }

        $days = max(1, (int) setting('sms_expire_days', '3'));
        $dry  = (bool) $this->option('dry');

        // ---------------- ۱) نزدیک به انقضا ----------------
        // اشتراک‌هایی که دقیقاً N روزِ تنظیم‌شده مانده دارند (هر اشتراک فقط
        // یک بار هشدار می‌گیرد و با اجرای روزانه کران تکراری نمی‌شود)
        $expiring = Subscription::query()
            ->where('status', 'active')
            ->whereDate('end_date', now()->addDays($days)->toDateString())
            ->whereHas('customer')
            ->with('customer', 'plan')
            ->get();

        $this->info("اشتراک‌های با {$days} روز مانده: " . $expiring->count());

        foreach ($expiring as $subscription) {
            $this->line("  #{$subscription->id} → {$subscription->customer?->phone}");

            if ($dry) {
                continue;
            }

            $sms->send('subscription_expiring', $subscription->customer?->phone, [
                'customer_name' => $subscription->customer?->name ?? 'مشتری',
                'plan_name'     => $subscription->plan?->name ?? 'اشتراک',
                'days_left'     => fa_num($days),
                'end_date'      => verta_date($subscription->end_date, 'Y/m/d') ?? (string) $subscription->end_date,
            ], $subscription);
        }

        // ---------------- ۲) تازه منقضی‌شده (دیروز) ----------------
        $expired = Subscription::query()
            ->where('status', 'active')
            ->whereDate('end_date', now()->subDay()->toDateString())
            ->whereHas('customer')
            ->with('customer', 'plan')
            ->get();

        $this->info('اشتراک‌های تازه منقضی‌شده: ' . $expired->count());

        foreach ($expired as $subscription) {
            $this->line("  #{$subscription->id} → {$subscription->customer?->phone}");

            if ($dry) {
                continue;
            }

            $sms->send('subscription_expired', $subscription->customer?->phone, [
                'customer_name' => $subscription->customer?->name ?? 'مشتری',
                'plan_name'     => $subscription->plan?->name ?? 'اشتراک',
                'end_date'      => verta_date($subscription->end_date, 'Y/m/d') ?? (string) $subscription->end_date,
            ], $subscription);
        }

        $this->info('انجام شد.');

        return self::SUCCESS;
    }
}
