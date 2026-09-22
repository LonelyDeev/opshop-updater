<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchase;
use App\Services\LicenseService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use packages\shetabit\payment\src\Facade\Payment;
use Shetabit\Payment\Models\Transaction;

class ApiPaymentCallbackController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private LicenseService $licenseService
    ) {}

    /* ===================================================================
     *  GET /api/v1/payments/callback
     *  کال‌بک درگاه shetabit (کاربر از درگاه اینجا برمی‌گرده)
     *  Query: transaction_id (یا Authority برای زرین‌پال)
     * =================================================================== */
    public function callback(Request $request)
    {
        $transactionId = $request->input('transaction_id')
            ?? $request->input('Authority')
            ?? $request->input('tracking_code')
            ?? $request->input('token');

        if (!$transactionId) {
            return redirect()->route('shop.home')
                ->with('error', 'اطلاعات تراکنش ناقص است.');
        }

        $purchase = PackagePurchase::where('transaction_id', $transactionId)->first();

        if (!$purchase) {
            return redirect()->route('shop.home')
                ->with('error', 'رکورد خرید یافت نشد.');
        }

        // تأیید پرداخت
        $result = $this->paymentService->verifyPayment($transactionId);

        if ($result['paid'] ?? false) {
            // خرید موفق - ریدایرکت به callback_url پروژه خریدار
            $callbackUrl = $purchase->callback_url;
            if ($callbackUrl) {
                $separator = str_contains($callbackUrl, '?') ? '&' : '?';
                $finalUrl = $callbackUrl . $separator . http_build_query([
                        'transaction_id' => $transactionId,
                        'status'         => 'success',
                        'purchase_id'    => $purchase->id,
                    ]);

                return redirect($finalUrl);
            }

            return redirect()->route('payment.result', $purchase)
                ->with('success', 'پرداخت با موفقیت تأیید شد.');
        }

        // پرداخت ناموفق
        if ($purchase->callback_url) {
            $separator = str_contains($purchase->callback_url, '?') ? '&' : '?';
            $finalUrl = $purchase->callback_url . $separator . http_build_query([
                    'transaction_id' => $transactionId,
                    'status'         => 'failed',
                    'error'          => $result['message'] ?? 'پرداخت ناموفق بود.',
                ]);

            return redirect($finalUrl);
        }

        return redirect()->route('payment.result', $purchase)
            ->with('error', $result['message'] ?? 'پرداخت ناموفق بود.');
    }
}
