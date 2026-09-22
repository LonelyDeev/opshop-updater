<?php

function get_gateway_configs($gateway)
{
    $gateway = \App\Models\Gateway::where('key', $gateway)->first();

    $configs = [];

    switch ($gateway->key) {
        case "zarinpal": {
            $configs['merchantId'] = $gateway->config('merchantId');
            break;
        }
        case "toman": {
            $user = auth()->user();
            $order =  $user->orders->last();
            $items = [];
            foreach ($order->items as $product) {

                $data = [
                    'name' => $product->title,
                    'price' => $product->price . 0,
                    'quantity' => $product->quantity
                ];
                array_push($items, $data);
                $data = null;
            }

            if ($order->shipping_cost > 0) {
                $shipping = [
                    'name' => $order->carrier->title,
                    'price' => $order->shipping_cost . 0,
                    'quantity' => 1
                ];
                array_push($items, $shipping);
            }

            $data = [
                'res_number' => $order->id,
                'return_to' => route('front.orders.verify', ['gateway' => 'toman']),
                'items' => $items
            ];

            $configs['shop_slug'] = $gateway->config('shop_slug');
            $configs['auth_code'] = $gateway->config('auth_code');
            $configs['data'] = $data;
            break;
        }
        case "payping": {
            $configs['merchantId'] = $gateway->config('merchantId');
            break;
        }
        case "irankish": {
            $configs['terminalId'] = $gateway->config('terminalId');
            $configs['password']   = $gateway->config('password');
            $configs['acceptorId'] = $gateway->config('acceptorId');
            $configs['pubKey']     = $gateway->config('pubKey');
            break;
        }
        case "idpay": {
            $configs['merchantId'] = $gateway->config('merchantId');
            break;
        }
        case "saman": {
            $configs['merchantId'] = $gateway->config('merchantId');
            break;
        }
        case "behpardakht": {
            $configs['terminalId'] = $gateway->config('terminalId');
            $configs['username']   = $gateway->config('username');
            $configs['password']   = $gateway->config('password');
            break;
        }
        case "payir": {
            $configs['merchantId'] = $gateway->config('merchantId');
            break;
        }
        case "sepehr": {
            $configs['terminalId'] = $gateway->config('terminalId');
            break;
        }
        case "sadad": {
            $configs['key']        = $gateway->config('key');
            $configs['merchantId'] = $gateway->config('merchantId');
            $configs['terminalId'] = $gateway->config('terminalId');
            break;
        }
        case "zibal": {
            $configs['merchantId'] = $gateway->config('merchantId');
            break;
        }
    }

    return $configs;
}

/* ------------------------------------------------------------------ */
/*  Presentation helpers                                                */
/* ------------------------------------------------------------------ */

if (! function_exists('verta_date')) {
    /**
     * Format a date as Jalali (شمسی).
     */
    function verta_date($date, string $format = 'Y/m/d'): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return \Morilog\Jalali\Jalalian::forge($date)->format($format);
        } catch (\Throwable) {
            return null;
        }
    }
}

if (! function_exists('verta_from_now')) {
    /**
     * Jalali relative time, e.g. "۳ روز پیش".
     */
    function verta_from_now($date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            return \Morilog\Jalali\Jalalian::forge($date)->ago();
        } catch (\Throwable) {
            return null;
        }
    }
}

if (! function_exists('fa_num')) {
    /**
     * Convert digits to Persian numerals.
     */
    function fa_num(string|int|float|null $value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}

if (! function_exists('en_num')) {
    /**
     * Convert Persian/Arabic digits to Latin numerals.
     */
    function en_num(string $value): string
    {
        return strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }
}

if (! function_exists('money')) {
    /**
     * Human money format (تومان).
     */
    function money(string|int|float|null $amount, bool $withUnit = true): string
    {
        $formatted = number_format((float) en_num((string) $amount));

        return $withUnit ? $formatted . ' تومان' : $formatted;
    }
}

if (! function_exists('bytes_human')) {
    /**
     * Human readable file size.
     */
    function bytes_human($bytes, int $precision = 1): ?string
    {
        if (blank($bytes)) {
            return null;
        }

        $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت', 'ترابایت'];
        $index = 0;
        $size = (float) en_num((string) $bytes);

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, $precision) . ' ' . $units[$index];
    }
}
