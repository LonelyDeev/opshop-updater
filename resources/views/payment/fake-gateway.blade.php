<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'تست' }} — درگاه پرداخت</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Tahoma, Arial, sans-serif; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a2a3a 0%, #0d1520 100%);
            padding: 16px;
        }
        .gw-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .45);
        }
        .gw-head {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .gw-head .logo {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .2);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .gw-head h1 { font-size: 17px; font-weight: 700; }
        .gw-head p { font-size: 11.5px; opacity: .9; margin-top: 3px; }
        .gw-body { padding: 24px; }
        .gw-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 11px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 13.5px;
        }
        .gw-row .k { color: #6b7280; }
        .gw-row .v { font-weight: 700; color: #111827; }
        .gw-row.total { background: #fef3c7; margin: 8px -24px 0; padding: 14px 24px; border-bottom: 0; }
        .gw-row.total .k { color: #92400e; font-weight: 700; }
        .gw-row.total .v { color: #92400e; font-size: 18px; }
        .gw-actions { display: grid; gap: 10px; margin-top: 20px; }
        @media (min-width: 400px) { .gw-actions { grid-template-columns: 1fr 1fr; } }
        .gw-btn {
            display: block;
            text-align: center;
            text-decoration: none;
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            border: 0;
            width: 100%;
            transition: background .15s ease;
        }
        .gw-btn-pay { background: #059669; color: #fff; }
        .gw-btn-pay:hover { background: #047857; }
        .gw-btn-fail { background: #dc2626; color: #fff; }
        .gw-btn-fail:hover { background: #b91c1c; }
        .gw-note {
            margin-top: 16px;
            font-size: 11px;
            color: #9ca3af;
            text-align: center;
            line-height: 1.8;
        }
        .gw-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
            font-size: 10.5px;
            border-radius: 999px;
            padding: 3px 10px;
            margin-top: 2px;
        }
        .gw-hint {
            margin-top: 12px;
            display: grid;
            gap: 4px;
            font-size: 11.5px;
            line-height: 1.7;
            color: #6b7280;
        }
        .gw-hint span { display: inline-flex; align-items: center; gap: 6px; }
        .dot { width: 8px; height: 8px; border-radius: 999px; flex-shrink: 0; }
        .dot-ok { background: #059669; }
        .dot-no { background: #dc2626; }
    </style>
</head>
<body>
    <div class="gw-card">
        <div class="gw-head">
            <div class="logo">&#128179;</div>
            <div>
                <h1>{{ $title ?? 'تست' }}</h1>
                <p>{{ $subtitle ?? 'درگاه داخلی توسعه — بدون کلید و بدون پرداخت واقعی' }}</p>
            </div>
        </div>
        <div class="gw-body">
            <div class="gw-row">
                <span class="k">پکیج</span>
                <span class="v">{{ $package?->name ?? '-' }}</span>
            </div>
            <div class="gw-row">
                <span class="k">طرح</span>
                <span class="v">{{ $plan?->name ?? '-' }}</span>
            </div>
            <div class="gw-row">
                <span class="k">شماره تراکنش</span>
                <span class="v" dir="ltr">{{ $purchase->transaction_id }}</span>
            </div>
            <div class="gw-row total">
                <span class="k">مبلغ قابل پرداخت</span>
                <span class="v">{{ number_format((float) $amount) }} ریال</span>
            </div>
            <div class="gw-actions">
                <a class="gw-btn gw-btn-pay" href="{{ $successUrl }}">{{ $payButton ?? 'پرداخت موفق' }}</a>
                <a class="gw-btn gw-btn-fail" href="{{ $cancelUrl }}">{{ $cancelButton ?? 'پرداخت ناموفق' }}</a>
            </div>
            <div class="gw-hint">
                <span><i class="dot dot-ok"></i> «پرداخت موفق» دقیقاً کاری را می‌کند که درگاه واقعی پس از تأیید پرداخت انجام می‌دهد.</span>
                <span><i class="dot dot-no"></i> «پرداخت ناموفق» مسیر ناموفق درگاه واقعی را شبیه‌سازی می‌کند.</span>
            </div>
            <p class="gw-note">
                <span class="gw-badge">درگاه تستی</span><br>
                این درگاه فقط برای توسعه و تست داخلی است؛ پول واقعی جابه‌جا نمی‌شود.
            </p>
        </div>
    </div>
</body>
</html>
