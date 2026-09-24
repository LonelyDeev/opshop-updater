<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchase;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * کال‌بک پرداخت فروشگاه عمومی (web):
 * درگاه بانکی پس از پرداخت کاربر را به /payment/callback برمی‌گرداند.
 * هم GET و هم POST پشتیبانی می‌شود (POST درگاه‌ها CSRF ندارند →
 * مسیر payment/callback در استثناهای VerifyCsrfToken قرار دارد).
 *
 * پس از تأیید پرداخت:
 *  - خریدهای API (callback_url بیرونی) → صفحه‌ی «نتیجه پرداخت» (payment/return)
 *    تا وضعیت + شمارش معکوس ۱۰ ثانیه‌ای نمایش داده شود و سپس به فروشگاه برگردد.
 *  - خریدهای ویترین خود پنل → صفحه‌ی نتیجه‌ی خود پنل (payment/result) مانند قبل.
 */
class WebPaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
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
        $given = [parse_url($url, PHP_URL_HOST), rtrim((string) parse_url($url, PHP_URL_PATH), '/')];

        foreach ([route('payment.callback'), route('api.packages.payment.callback')] as $panelUrl) {
            $panel = [parse_url($panelUrl, PHP_URL_HOST), rtrim((string) parse_url($panelUrl, PHP_URL_PATH), '/')];

            if ($given === $panel) {
                return true;
            }
        }

        return false;
    }
}
