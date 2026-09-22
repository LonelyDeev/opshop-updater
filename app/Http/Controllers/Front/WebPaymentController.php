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
 */
class WebPaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function callback(Request $request): RedirectResponse
    {
        // شناسه تراکنش با کلیدهای متداول درگاه‌ها (همان جریان API)
        $transactionId = $request->input('transaction_id')
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

        return redirect()
            ->route('payment.result', $purchase)
            ->with(
                ($result['paid'] ?? false) ? 'success' : 'error',
                $result['message'] ?? (($result['paid'] ?? false) ? 'پرداخت با موفقیت تأیید شد.' : 'پرداخت ناموفق بود.')
            );
    }
}
