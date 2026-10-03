<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;

/**
 * درایور ایده پردازان (RestfulSms) — UltraFastSend (پترن)
 * احراز با UserApiKey + SecretKey داخل بدنه درخواست.
 */
class IdehPardazanDriver implements SmsDriver
{
    private const SEND_URL = 'https://RestfulSms.com/api/UltraFastSend/direct';

    private string $apiKey;
    private string $secretKey;

    public function __construct(?string $apiKey = null, ?string $secretKey = null)
    {
        $this->apiKey    = (string) ($apiKey ?? setting('sms_idehpardazan_apikey'));
        $this->secretKey = (string) ($secretKey ?? setting('sms_idehpardazan_secretkey'));
    }

    public function name(): string
    {
        return 'idehpardazan';
    }

    public function supportsText(): bool
    {
        return false; // UltraFastSend فقط پترن
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->secretKey !== '';
    }

    public function missingConfigHint(): string
    {
        return 'کلید API و SecretKey ایده پردازان تنظیم نشده است.';
    }

    /**
     * پترن: TemplateId + ParameterArray [{Parameter, ParameterValue}]
     * نام Parameter باید با placeholder ثبت‌شده در قالب ایده پردازان یکی باشد.
     */
    public function sendPattern(string $mobile, string $pattern, array $values, string $rendered): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'response' => $this->missingConfigHint()];
        }

        $parameters = [];
        foreach ($values as $name => $value) {
            $parameters[] = [
                'Parameter'      => (string) $name,
                'ParameterValue' => (string) $value,
            ];
        }

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(20)
                ->connectTimeout(10)
                ->post(self::SEND_URL, [
                    'mobile'         => $mobile,
                    'UserApiKey'     => $this->apiKey,
                    'SecretKey'      => $this->secretKey,
                    'TemplateId'     => $pattern,
                    'ParameterArray' => $parameters,
                ]);

            $json = $response->json();
            $body = (string) $response->body();

            // پاسخ موفق: {"IsSuccessful":true,...} یا {"Successful":true,...}
            $ok = $response->successful()
                && (data_get($json, 'IsSuccessful') === true
                    || data_get($json, 'Successful') === true);

            return ['ok' => $ok, 'response' => $body];
        } catch (\Throwable $e) {
            return ['ok' => false, 'response' => $e->getMessage()];
        }
    }

    public function sendText(string $mobile, string $text): array
    {
        return ['ok' => false, 'response' => 'ارسال متن خام در ایده پردازان پشتیبانی نمی‌شود؛ کد Template قالب را تنظیم کنید.'];
    }
}
