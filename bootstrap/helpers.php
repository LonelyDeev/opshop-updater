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
