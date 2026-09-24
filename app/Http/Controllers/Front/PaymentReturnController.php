<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchase;
use App\Services\PaymentService;
use Illuminate\Http\Request;

/**
 * صفحه «نتیجه پرداخت» — مرحله میانی بین درگاه و فروشگاه:
 *
 * پس از بازگشت از درگاه و تأیید پرداخت، کاربر به این صفحه می‌آید؛ وضعیت پرداخت
 * (موفق / ناموفق / در انتظار) همراه با جزئیات تراکنش نمایش داده می‌شود و کاربر با
 * دکمه «بازگشت به فروشگاه» یا پس از شمارش معکوس ۱۰ ثانیه‌ای، به‌صورت خودکار به
 * callback_url ارسالی از سمت فروشگاه (هنگام ایجاد خرید) بازگردانده می‌شود.
 */
class PaymentReturnController extends Controller
{
    /** پارامترهای درگاه که هنگام بازگشت به فروشگاه حفظ (forward) می‌شوند */
    private const FORWARD_KEYS = [
        'transactionId', 'transaction_id', 'Authority',
        'Status', 'status', 'cancel', 'token', 'tracking_code',
    ];

    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function show(Request $request, PackagePurchase $purchase)
    {
        $purchase->load([
            'package:id,name,slug',
            'pricingPlan:id,name',
            'customer:id,name',
            'license:id,license_key,expires_at',
            'version:id,version',
        ]);

        // اگر خرید هنوز در انتظار است (مثلاً کال‌بک درگاه دیر رسیده)، یک‌بار تأیید را امتحان می‌کنیم
        if ($purchase->status === PackagePurchase::STATUS_PENDING && $purchase->transaction_id) {
            $this->paymentService->verifyPayment($purchase->transaction_id, renew: true);
            $purchase->refresh();
        }

        [$returnUrl, $returnHost, $isInternal] = $this->resolveReturnTarget($request, $purchase);

        return view('payment.return', [
            'purchase'    => $purchase,
            'status'      => $purchase->status, // paid | failed | pending
            'message'     => $this->statusMessage($purchase),
            'gatewayName' => config("general.supported_gateways.{$purchase->gateway}") ?? $purchase->gateway,
            'returnUrl'   => $returnUrl,
            'returnHost'  => $returnHost,
            'isInternal'  => $isInternal,
            'seconds'     => 10,
        ]);
    }

    /**
     * مقصد نهایی بازگشت:
     *  - خرید API (callback_url بیرونی) → همان آدرس + پارامترهای وضعیت/تراکنش
     *  - خرید ویترین خود پنل یا callback داخلی → صفحه نتیجه خود پنل (بدون حلقه)
     *
     * @return array{0: string, 1: ?string, 2: bool} [url, host, isInternal]
     */
    private function resolveReturnTarget(Request $request, PackagePurchase $purchase): array
    {
        $callback = $purchase->callback_url;

        if (!$callback || $this->isInternalCallback($callback)) {
            $url = route('payment.result', $purchase);

            return [$url, null, true];
        }

        $status = match ($purchase->status) {
            PackagePurchase::STATUS_PAID   => 'success',
            PackagePurchase::STATUS_FAILED => 'failed',
            default                        => 'pending',
        };

        $params = [
            'transaction_id' => $purchase->transaction_id,
            'status'         => $status,
        ];

        if ($purchase->isPaid()) {
            $params['purchase_id'] = $purchase->id;
        } else {
            $params['error'] = $purchase->meta['fail_reason'] ?? 'پرداخت ناموفق بود.';
        }

        // پارامترهای اصلی درگاه (مثل transactionId یا cancel) به فروشگاه هم forward می‌شود
        foreach (self::FORWARD_KEYS as $key) {
            if ($request->filled($key) && !array_key_exists($key, $params)) {
                $params[$key] = $request->input($key);
            }
        }

        $separator = str_contains($callback, '?') ? '&' : '?';

        return [
            $callback . $separator . http_build_query($params),
            parse_url($callback, PHP_URL_HOST) ?: $callback,
            false,
        ];
    }

    /**
     * آیا callback_url به یکی از مسیرهای کال‌بک خود پنل اشاره می‌کند؟
     * (برای جلوگیری از حلقه‌ی بی‌نهایت، این موارد به صفحه‌ی نتیجه‌ی خود پنل هدایت می‌شوند)
     */
    private function isInternalCallback(string $url): bool
    {
        $given = [parse_url($url, PHP_URL_HOST), rtrim((string) parse_url($url, PHP_URL_PATH), '/')];

        foreach ([route('payment.callback'), route('api.packages.payment.callback')] as $panelUrl) {
            $panel = [parse_url($panelUrl, PHP_URL_HOST), rtrim((string) parse_url($panelUrl, PHP_URL_PATH), '/')];

            if ($given === $panel) {
                return true;
            }
        }

        return false;
    }

    private function statusMessage(PackagePurchase $purchase): string
    {
        return match ($purchase->status) {
            PackagePurchase::STATUS_PAID   => 'پرداخت شما با موفقیت تأیید شد و لایسنس پکیج صادر گردید.',
            PackagePurchase::STATUS_FAILED => $purchase->meta['fail_reason'] ?? 'متأسفانه پرداخت شما تأیید نشد؛ مبلغی از حساب شما کسر نشده است.',
            default                        => 'پرداخت هنوز توسط درگاه تأیید نشده است. پس از بازگشت به فروشگاه، وضعیت تراکنش بررسی می‌شود.',
        };
    }
}
