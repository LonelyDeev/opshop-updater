<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchase;
use App\Models\SubscriptionOrder;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * کال‌بک پرداخت فروشگاه عمومی (web):
 * درگاه بانکی پس از پرداخت کاربر را به /payment/callback برمی‌گرداند.
 * هم GET و هم POST پشتیبانی می‌شود (POST درگاه‌ها CSRF ندارند →
 * مسیر payment/callback در استثناهای VerifyCsrfToken قرار دارد).
 *
 * دو نوع تراکنش از این مسیر عبور می‌کنند:
 *  ۱) خرید پکیج (PackagePurchase) — مانند قبل
 *  ۲) سفارش اشتراک (SubscriptionOrder) — پس از تأیید پرداخت، به صفحه نتیجه
 *     اشتراک می‌رود (پرداخت موفق + «در انتظار تأیید مدیر»).
 *
 * پس از تأیید پرداخت:
 *  - خریدهای API (callback_url بیرونی) → صفحه‌ی «نتیجه پرداخت» (payment/return)
 *    تا وضعیت + شمارش معکوس ۱۰ ثانیه‌ای نمایش داده شود و سپس به فروشگاه برگردد.
 *  - خریدهای ویترین خود پنل → صفحه‌ی نتیجه‌ی خود پنل (payment/result) مانند قبل.
 */
class WebPaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private SubscriptionService $subscriptionService
    ) {}

    public function callback(Request $request): RedirectResponse
    {
        // شناسه تراکنش با کلیدهای متداول درگاه‌ها (همان جریان API)
        // transactionId (camelCase) → درگاه آزمایشی local
        $transactionId = $request->input('transaction_id')
            ?? $request->input('transactionId')
            ?? $request->input('Authority')
            ?? $request->input('tracking_code')
            ?? $request->input('token');

        if (!$transactionId) {
            return redirect()
                ->route('shop.home')
                ->with('error', 'اطلاعات تراکنش ناقص است.');
        }

        // ---------- ۱) سفارش اشتراک (طرح اشتراک) ----------
        $subscriptionOrder = SubscriptionOrder::where('transaction_id', $transactionId)->first();

        if ($subscriptionOrder) {
            $result = $this->subscriptionService->verifyPayment($transactionId);
            $subscriptionOrder->refresh();

            // سفارش API (callback_url بیرونی) → صفحه نتیجه + شمارش معکوس، سپس فروشگاه
            if ($subscriptionOrder->callback_url && !$this->isInternalCallback($subscriptionOrder->callback_url)) {
                return redirect()->route('payment.return.subscription', array_merge($request->query(), [
                    'order' => $subscriptionOrder->id,
                ]));
            }

            return redirect()
                ->route('subscription.result', $subscriptionOrder)
                ->with(
                    ($result['paid'] ?? false) ? 'success' : 'error',
                    $result['message'] ?? (($result['paid'] ?? false) ? 'پرداخت با موفقیت تأیید شد.' : 'پرداخت ناموفق بود.')
                );
        }

        // ---------- ۲) خرید پکیج (مانند قبل) ----------
        $purchase = PackagePurchase::where('transaction_id', $transactionId)->first();

        if (!$purchase) {
            return redirect()
                ->route('shop.home')
                ->with('error', 'رکورد خرید یافت نشد.');
        }

        // تأیید پرداخت + صدور/تمدید لایسنس
        $result = $this->paymentService->verifyPayment($transactionId, renew: true);

        $purchase->refresh();

        // خرید فروشگاه (API): ابتدا صفحه‌ی نتیجه + شمارش معکوس، سپس بازگشت به فروشگاه
        if ($purchase->callback_url && !$this->isInternalCallback($purchase->callback_url)) {
            return redirect()->route('payment.return', array_merge($request->query(), [
                'purchase' => $purchase->id,
            ]));
        }

        return redirect()
            ->route('payment.result', $purchase)
            ->with(
                ($result['paid'] ?? false) ? 'success' : 'error',
                $result['message'] ?? (($result['paid'] ?? false) ? 'پرداخت با موفقیت تأیید شد.' : 'پرداخت ناموفق بود.')
            );
    }

    /**
     * آیا callback_url به یکی از مسیرهای کال‌بک خود پنل اشاره می‌کند؟
     */
    private function isInternalCallback(string $url): bool
    {
        // هاست + پورت (پورت را هم مقایسه می‌کنیم تا localhost:3001 با localhost:8000 یکی تلقی نشود)
        $given = [$this->hostWithPort($url), rtrim((string) parse_url($url, PHP_URL_PATH), '/')];

        foreach ([route('payment.callback'), route('api.packages.payment.callback')] as $panelUrl) {
            $panel = [$this->hostWithPort($panelUrl), rtrim((string) parse_url($panelUrl, PHP_URL_PATH), '/')];

            if ($given === $panel) {
                return true;
            }
        }

        return false;
    }

    /** هاست به‌همراه پورت (اگر وجود داشته باشد) برای مقایسه‌ی دقیق‌تر callback_url */
    private function hostWithPort(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        return $port ? $host . ':' . $port : $host;
    }
}
