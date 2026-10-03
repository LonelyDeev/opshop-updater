<?php

namespace App\Services\Sms\Contracts;

/**
 * قرارداد درایور پیامک
 *
 * هر درایور دو حالت ارسال دارد:
 *  - sendPattern: ارسال با پترن/قالب ثبت‌شده در پنل سرویس (استاندارد پیامک‌های تراکنشی ایران)
 *  - sendText: ارسال متن خام (اگر درایور پشتیبانی کند)
 */
interface SmsDriver
{
    /**
     * نام یکتای درایور (kavenegar, melipayamak, ippanel, farazsms, idehpardazan)
     */
    public function name(): string;

    /**
     * آیا این درایور ارسال متن خام (بدون پترن) را پشتیبانی می‌کند؟
     */
    public function supportsText(): bool;

    /**
     * آیا تنظیمات لازم این درایور تکمیل است؟
     */
    public function isConfigured(): bool;

    /**
     * ارسال با پترن
     *
     * @param  string $mobile   شماره موبایل (نرمال 09xxxxxxxxx)
     * @param  string $pattern  کد پترن در پنل سرویس
     * @param  array  $values   مقادیر متغیرها (varName => value)
     * @param  string $rendered متن رندرشده (برای درایورهایی که متن خام می‌گیرند)
     * @return array{ok: bool, response: string}
     */
    public function sendPattern(string $mobile, string $pattern, array $values, string $rendered): array;

    /**
     * ارسال متن خام (اختیاری)
     *
     * @return array{ok: bool, response: string}
     */
    public function sendText(string $mobile, string $text): array;

    /**
     * پیام راهنمای فیلدهای تنظیم نشده
     */
    public function missingConfigHint(): string;
}
