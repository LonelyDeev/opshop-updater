<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * کش WSDL برای درگاه‌های SOAP (مثل سامان/سپ)
 *
 * مشکل: روی خیلی از هاست‌های اشتراکی، SoapClient نمی‌تواند WSDL ریموت را
 * بخواند (allow_url_fopen خاموش / محدودیت TLS / فایروال خروجی) و خطای
 * «SOAP-ERROR: Parsing WSDL: Couldn't load from ...» می‌دهد.
 *
 * راه‌حل: WSDL با cURL (TLS 1.2 + تایم‌اوت) دانلود، ارجاعات schemaLocation/
 * location هم بازنویسی و به‌صورت محلی در storage/app/wsdl-cache کش می‌شود.
 * سپس مسیر لوکال به SoapClient داده می‌شود (اکشن SOAP خودش روی سوکت مستقیم
 * انجام می‌شود و به allow_url_fopen وابسته نیست).
 */
class WsdlCache
{
    private string $cacheDir;

    /** مدت اعتبار کش (ثانیه) — ۲۴ ساعت */
    public const TTL = 86400;

    public function __construct()
    {
        $this->cacheDir = storage_path('app/wsdl-cache');

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * مسیر محلی WSDL را برمی‌گرداند؛ در صورت لزوم دانلود/بازخوانی می‌کند.
     *
     * @throws RuntimeException وقتی نه کش دارد و نه دانلود ممکن است
     */
    public function resolve(string $wsdlUrl, string $cacheKey): string
    {
        $cacheKey = preg_replace('/[^a-zA-Z0-9_-]/', '-', $cacheKey) ?: 'wsdl';
        $localFile = $this->cacheDir . '/' . $cacheKey . '.wsdl';

        // ۱) کش تازه موجود است؟
        if (is_file($localFile) && (time() - filemtime($localFile)) < self::TTL) {
            return $localFile;
        }

        // ۲) دانلود تازه با cURL
        try {
            $content = $this->download($wsdlUrl);

            if ($content && str_contains($content, '<')) { // ظاهر WSDL/XML دارد
                // ارجاعات خارجی (xsd:import / wsdl:import / location) را هم محلی کن
                $content = $this->localizeReferences($content, $wsdlUrl, $cacheKey);

                file_put_contents($localFile, $content, LOCK_EX);
                @chmod($localFile, 0644);

                return $localFile;
            }
        } catch (\Throwable $e) {
            Log::warning('WSDL download failed, using stale cache if any', [
                'url'   => $wsdlUrl,
                'error' => $e->getMessage(),
            ]);
        }

        // ۳) کش قدیمی هست؟ (بهتر از خطاست)
        if (is_file($localFile)) {
            return $localFile;
        }

        // ۴) آخرین تلاش: خود SoapClient مستقیم (شاید روی بعضی سرورها بخواند)
        return $wsdlUrl;
    }

    /**
     * دانلود با cURL — TLS 1.2، تایم‌اوت ۲۰ ثانیه
     */
    private function download(string $url): ?string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Laravel-Payment/1.0)',
            CURLOPT_HTTPHEADER     => ['Accept: text/xml, application/xml, */*'],
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $body === null || $body === '' || $status >= 400) {
            throw new RuntimeException(
                'دانلود WSDL ناموفق بود' . ($error ? " ({$error})" : '') . " — HTTP {$status}"
            );
        }

        return (string) $body;
    }

    /**
     * ارجاع‌های داخل WSDL را به فایل‌های محلی بازنویسی می‌کند.
     *
     * فقط تگ‌های <xsd:import schemaLocation="…"> / <wsdl:import location="…">
     * بازنویسی می‌شوند؛ soap:address location (endpoint واقعی سرویس) دست‌نخورده
     * می‌ماند تا فراخوانی SOAP به آدرس واقعی درگاه برود.
     */
    private function localizeReferences(string $wsdl, string $baseUrl, string $cacheKey): string
    {
        return preg_replace_callback(
            '/<(?:\w+:)?import\b[^>]*?\b(schemaLocation|location)\s*=\s*"([^"]+)"/i',
            function (array $m) use ($baseUrl, $cacheKey) {
                $refUrl = $m[2];

                // نسبی → مطلق
                if (!preg_match('#^https?://#i', $refUrl)) {
                    $parts  = parse_url($baseUrl);
                    $dir    = rtrim(str_replace('\\', '/', dirname($parts['path'] ?? '/')), '/');
                    $refUrl = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $dir . '/' . ltrim($refUrl, '/');
                }

                try {
                    $content = $this->download($refUrl);
                    $safe    = preg_replace('/[^a-zA-Z0-9._-]/', '-', basename(parse_url($refUrl, PHP_URL_PATH) ?: 'ref')) ?: 'ref';
                    $refFile = $this->cacheDir . '/' . $cacheKey . '-' . $safe;

                    file_put_contents($refFile, $content, LOCK_EX);

                    return $m[1] . '="' . $refFile . '"';
                } catch (\Throwable) {
                    // اگر ارجاع دانلود نشد، همان ریموت بماند (SoapClient ممکن است بتواند بخواند)
                    return $m[0];
                }
            },
            $wsdl
        ) ?? $wsdl;
    }

    /** پاک‌سازی دستی کش (برای تست) */
    public function flush(?string $cacheKey = null): void
    {
        $pattern = $cacheKey
            ? $this->cacheDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $cacheKey) . '*'
            : $this->cacheDir . '/*';

        foreach (glob($pattern) ?: [] as $file) {
            @unlink($file);
        }
    }
}
