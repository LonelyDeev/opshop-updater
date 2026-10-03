<?php
/**
 * تولید لوگوهای پیش‌فرض درگاه‌های پرداخت (SVG)
 * هر درگاه: کارت سفید با نشان رنگی برند + نام + زیرنویس
 *
 * اجرا: php generate-gateway-logos.php
 * خروجی: public/uploads/gateways/logos/{key}.svg
 */

$gateways = [
    // key => [رنگ برند, نام فارسی, زیرنویس/بانک]
    'behpardakht' => ['#E87722', 'به‌پرداخت', 'بانک ملت'],
    'saman'       => ['#16528E', 'سامان', 'کلاسیک (SOAP)'],
    'sep'         => ['#1E9E8A', 'سپ', 'سامان SEP'],
    'sepehr'      => ['#0E7A4F', 'سپهر', 'بانک صادرات'],
    'sadad'       => ['#8A6D3B', 'سداد', 'بانک ملی'],
    'irankish'    => ['#C2185B', 'ایران‌کیش', 'پرداخت الکترونیک'],
    'zarinpal'    => ['#F5B50A', 'زرین‌پال', 'درگاه مستقیم'],
    'zibal'       => ['#7B1FA2', 'زیبال', 'درگاه پرداخت'],
    'idpay'       => ['#D81B60', 'آی‌دی‌پی', 'idpay.ir'],
    'payir'       => ['#00897B', 'پی‌آی‌ار', 'pay.ir'],
    'payping'     => ['#F4511E', 'پی‌پینگ', 'payping.ir'],
    'toman'       => ['#5D4037', 'تومن', 'toman.so'],
    'local'       => ['#616161', 'درگاه آزمایشی', 'تست — پول واقعی کسر نمی‌شود'],
];

$outDir = __DIR__ . '/public/uploads/gateways/logos';
@mkdir($outDir, 0775, true);

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

foreach ($gateways as $key => [$color, $name, $sub]) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="96" viewBox="0 0 240 96" role="img" aria-label="{$name}">
  <defs>
    <linearGradient id="bg-{$key}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#ffffff"/>
      <stop offset="1" stop-color="#f4f5f7"/>
    </linearGradient>
  </defs>
  <rect x="1" y="1" width="238" height="94" rx="14" fill="url(#bg-{$key})" stroke="#e2e4e9" stroke-width="2"/>
  <rect x="14" y="18" width="60" height="60" rx="14" fill="{$color}"/>
  <circle cx="44" cy="38" r="10" fill="rgba(255,255,255,0.28)"/>
  <path d="M30 62 L38 50 L46 56 L54 42 L60 50" stroke="rgba(255,255,255,0.85)" stroke-width="3.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
  <circle cx="53" cy="31" r="4.5" fill="#ffffff"/>
  <text x="88" y="46" font-family="Vazirmatn, Tahoma, sans-serif" font-size="21" font-weight="800" fill="#1f2430">{$name}</text>
  <text x="88" y="68" font-family="Vazirmatn, Tahoma, sans-serif" font-size="12.5" fill="#7a8194">{$sub}</text>
</svg>
SVG;

    $file = "{$outDir}/{$key}.svg";
    file_put_contents($file, $svg);
    @chmod($file, 0644);
    echo "✓ {$key}.svg\n";
}

echo "\n" . count($gateways) . " logos generated in {$outDir}\n";
