<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;

/**
 * درایور ملی‌پیامک — BaseServiceNumber (پترن) + SendSMS (متن خام) از طریق REST
 * (بدون SOAP تا روی هر هاستی کار کند)
 */
class MelipayamakDriver implements SmsDriver
{
    private const PATTERN_URL = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';
    private const TEXT_URL    = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';

    private string $username;
    private string $password;
    private string $from;

    public function __construct(?string $username = null, ?string $password = null, ?string $from = null)
    {
        $this->username = (string) ($username ?? setting('sms_melipayamak_username'));
        $this->password = (string) ($password ?? setting('sms_melipayamak_password'));
        $this->from     = (string) ($from ?? setting('sms_melipayamak_from'));
    }

    public function name(): string
    {
        return 'melipayamak';
    }

    public function supportsText(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '' && $this->from !== '';
    }

    public function missingConfigHint(): string
    {
        return 'نام کاربری، رمز عبور و شماره خط ملی‌پیامک تنظیم نشده است.';
    }

    /**
     * پترن: bodyId + مقادیر با ترتیب متغیرها جدا با ;
     */
    public function sendPattern(string $mobile, string $pattern, array $values, string $rendered): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'response' => $this->missingConfigHint()];
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->connectTimeout(10)
                ->post(self::PATTERN_URL, [
                    'username' => $this->username,
                    'password' => $this->password,
                    'to'       => $mobile,
                    'from'     => $this->from,
                    'text'     => implode(';', array_map(fn ($v) => (string) $v, array_values($values))),
                    'bodyId'   => $pattern,
                ]);

            $json = $response->json();

            // RetStatus=1 → موفق
            return [
                'ok'       => (int) ($json['RetStatus'] ?? 0) === 1,
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'response' => $e->getMessage()];
        }
    }

    public function sendText(string $mobile, string $text): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'response' => $this->missingConfigHint()];
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->connectTimeout(10)
                ->post(self::TEXT_URL, [
                    'username' => $this->username,
                    'password' => $this->password,
                    'to'       => $mobile,
                    'from'     => $this->from,
                    'text'     => $text,
                ]);

            $json = $response->json();

            return [
                'ok'       => (int) ($json['RetStatus'] ?? 0) === 1,
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'response' => $e->getMessage()];
        }
    }
}
