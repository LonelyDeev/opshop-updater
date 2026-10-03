<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;

/**
 * درایور کاوه‌نگار — VerifyLookup (پترن) + SmsSend (متن خام)
 * مستندات: https://kavenegar.com/rest.html
 */
class KavenegarDriver implements SmsDriver
{
    private const BASE = 'https://api.kavenegar.com/v1';

    private string $apiKey;
    private ?string $sender;

    public function __construct(?string $apiKey = null, ?string $sender = null)
    {
        $this->apiKey = (string) ($apiKey ?? setting('sms_kavenegar_apikey'));
        $this->sender = $sender;
    }

    public function name(): string
    {
        return 'kavenegar';
    }

    public function supportsText(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function missingConfigHint(): string
    {
        return 'کلید API کاوه‌نگار (sms_kavenegar_apikey) تنظیم نشده است.';
    }

    /**
     * پترن: token / token2 / token3 / token10 / token20 به ترتیب متغیرها
     */
    public function sendPattern(string $mobile, string $pattern, array $values, string $rendered): array
    {
        $tokens = array_values($values);
        $slots  = ['token', 'token2', 'token3', 'token10', 'token20'];

        $form = [
            'receptor' => $mobile,
            'template' => $pattern,
        ];

        foreach ($slots as $i => $slot) {
            if (isset($tokens[$i]) && $tokens[$i] !== '' && $tokens[$i] !== null) {
                $form[$slot] = (string) $tokens[$i];
            }
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->connectTimeout(10)
                ->post(self::BASE . '/' . rawurlencode($this->apiKey) . '/verify/lookup.json', $form);

            $json = $response->json();

            return [
                'ok'       => ($json['return']['status'] ?? 0) == 200,
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

        $form = [
            'receptor' => $mobile,
            'message'  => $text,
        ];

        if ($this->sender && $this->sender !== '') {
            $form['sender'] = $this->sender;
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->connectTimeout(10)
                ->post(self::BASE . '/' . rawurlencode($this->apiKey) . '/sms/send.json', $form);

            $json = $response->json();

            return [
                'ok'       => ($json['return']['status'] ?? 0) == 200,
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'response' => $e->getMessage()];
        }
    }
}
