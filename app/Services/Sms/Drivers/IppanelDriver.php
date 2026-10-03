<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;

/**
 * درایور آی‌پی‌پنل (ippanel.com) — ارسال پترنی
 * فقط پترن (shared-line) پشتیبانی می‌شود؛ برای ارسال باید کد پترن هر قالب
 * در پنل ippanel ثبت و در تنظیمات قالب وارد شود.
 */
class IppanelDriver implements SmsDriver
{
    private const PATTERN_URL = 'https://ippanel.com/patterns/pattern';

    private string $username;
    private string $password;
    private string $from;

    public function __construct(?string $username = null, ?string $password = null, ?string $from = null)
    {
        $this->username = (string) ($username ?? setting('sms_ippanel_username'));
        $this->password = (string) ($password ?? setting('sms_ippanel_password'));
        $this->from     = (string) ($from ?? setting('sms_ippanel_from'));
    }

    public function name(): string
    {
        return 'ippanel';
    }

    public function supportsText(): bool
    {
        return false; // ارسال از خط خدماتی فقط با پترن
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '' && $this->from !== '';
    }

    public function missingConfigHint(): string
    {
        return 'نام کاربری، رمز عبور و خط فرستنده آی‌پی‌پنل تنظیم نشده است.';
    }

    /**
     * پترن: input_data به‌صورت {متغیر: مقدار} — نام متغیرها باید با
     * placeholderهای ثبت‌شده در پترنِ پنل ippanel یکی باشد.
     */
    public function sendPattern(string $mobile, string $pattern, array $values, string $rendered): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'response' => $this->missingConfigHint()];
        }

        try {
            $response = Http::asForm()
                ->timeout(25)
                ->connectTimeout(10)
                ->post(self::PATTERN_URL, [
                    'username'     => $this->username,
                    'password'     => $this->password,
                    'from'         => $this->from,
                    'to'           => json_encode([$mobile]),
                    'input_data'   => json_encode($values, JSON_UNESCAPED_UNICODE),
                    'pattern_code' => $pattern,
                ]);

            $body = trim((string) $response->body());

            // پاسخ موفق معمولاً یک شناسه عددی است؛ خطا با کد منفی/متن
            $ok = $response->successful()
                && $body !== ''
                && !str_starts_with($body, '-')
                && !str_contains($body, 'error');

            return ['ok' => $ok, 'response' => $body];
        } catch (\Throwable $e) {
            return ['ok' => false, 'response' => $e->getMessage()];
        }
    }

    public function sendText(string $mobile, string $text): array
    {
        return ['ok' => false, 'response' => 'ارسال متن خام در آی‌پی‌پنل پشتیبانی نمی‌شود؛ کد پترن قالب را تنظیم کنید.'];
    }
}
