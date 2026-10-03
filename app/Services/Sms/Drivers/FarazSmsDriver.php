<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;

/**
 * درایور فراز اس‌ام‌اس (iranpayamak / FarazSMS) — ارسال پترنی
 * ارسال با هدر Api-Key؛ attributes لیست مقادیر به ترتیب placeholderهای پترن.
 */
class FarazSmsDriver implements SmsDriver
{
    private const PATTERN_URL = 'https://api.iranpayamak.com/ws/v1/sms/pattern';

    private string $apiKey;
    private string $from;

    public function __construct(?string $apiKey = null, ?string $from = null)
    {
        $this->apiKey = (string) ($apiKey ?? setting('sms_farazsms_apikey'));
        $this->from   = (string) ($from ?? setting('sms_farazsms_from'));
    }

    public function name(): string
    {
        return 'farazsms';
    }

    public function supportsText(): bool
    {
        return false; // خط خدماتی — فقط پترن
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function missingConfigHint(): string
    {
        return 'کلید API فراز اس‌ام‌اس (sms_farazsms_apikey) تنظیم نشده است.';
    }

    public function sendPattern(string $mobile, string $pattern, array $values, string $rendered): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'response' => $this->missingConfigHint()];
        }

        try {
            $response = Http::withHeaders([
                'Accept'  => 'application/json',
                'Api-Key' => $this->apiKey,
            ])
                ->timeout(20)
                ->connectTimeout(10)
                ->post(self::PATTERN_URL, [
                    'code'          => $pattern,
                    'attributes'    => array_values(array_map(fn ($v) => (string) $v, $values)),
                    'recipient'     => $mobile,
                    'line_number'   => $this->from,
                    'number_format' => 'english',
                ]);

            $json = $response->json();
            $body = (string) $response->body();

            // فراز: ok=true یا status=OK → موفق
            $ok = $response->successful()
                && (data_get($json, 'ok') === true
                    || data_get($json, 'status') === 'OK'
                    || data_get($json, 'status') === 200);

            return ['ok' => $ok, 'response' => $body];
        } catch (\Throwable $e) {
            return ['ok' => false, 'response' => $e->getMessage()];
        }
    }

    public function sendText(string $mobile, string $text): array
    {
        return ['ok' => false, 'response' => 'ارسال متن خام در فراز اس‌ام‌اس پشتیبانی نمی‌شود؛ کد پترن قالب را تنظیم کنید.'];
    }
}
