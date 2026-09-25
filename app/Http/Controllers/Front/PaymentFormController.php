<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchase;
use App\Models\SubscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * سرو کردن فرم پرداختِ درایورهای فرم‌محور و درگاه آزمایشی local.
 *
 * چرا لازم است؟ درایورهای بانکی (مثل به‌پرداخت/سامان) به‌جای URL ساده، فرم HTML
 * خود-ارسال برمی‌گردانند؛ این HTML در meta خرید ذخیره می‌شود و payment_url به
 * مسیر امضادار این کنترلر اشاره می‌کند. مرورگر مشتری (یا مرورگر صاحب فروشگاه)
 * به این مسیر می‌آید، فرم رندر و خودکار به درگاه POST می‌شود.
 *
 * برای درگاه آزمایشی local صفحه شبیه‌ساز درگاه رندر می‌شود تا کل جریان پرداخت
 * بدون درگاه واقعی قابل تست باشد.
 *
 * هم خرید پکیج و هم درخواست اشتراک پشتیبانی می‌شود:
 * پارامتر query «t=sub» (جزئی از امضای URL) مشخص می‌کند رکورد در
 * subscription_requests است (شناسه‌های دو جدول توالی‌های جدا دارند و ممکن است برخورد کنند).
 */
class PaymentFormController extends Controller
{
    public function show(Request $request, int $purchase)
    {
        // اعتبارسنجی امضای URL (۳۰ دقیقه اعتبار) — پارامتر t هم داخل امضا هست
        if (!$request->hasValidSignature()) {
            abort(403, 'لینک پرداخت نامعتبر یا منقضی شده است.');
        }

        $payable = $request->query('t') === 'sub'
            ? SubscriptionRequest::findOrFail($purchase)
            : PackagePurchase::findOrFail($purchase);

        // رکوردهای ناموفق/تأییدشده فرم ندارند
        $stillPending = $payable instanceof SubscriptionRequest
            ? in_array($payable->payment_status, [SubscriptionRequest::PAYMENT_PENDING, SubscriptionRequest::PAYMENT_FAILED], true)
            : $payable->status === PackagePurchase::STATUS_PENDING;

        if (!$stillPending) {
            return redirect()->route('shop.home')
                ->with('error', 'این تراکنش پرداخت دیگر در وضعیت انتظار نیست.');
        }

        // ۱) درگاه آزمایشی: صفحه شبیه‌ساز درگاه
        if ($payable->gateway === 'local') {
            return view('payment.fake-gateway', $this->fakeGatewayData($payable));
        }

        // ۲) درایورهای فرم‌محور واقعی: HTML ذخیره‌شده را برگردان
        $formHtml = $payable->meta['payment_form'] ?? null;

        if (!$formHtml) {
            abort(410, 'فرم پرداخت یافت نشد یا منقضی شده است. لطفاً خرید را دوباره آغاز کنید.');
        }

        return response($formHtml, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * داده‌های صفحه شبیه‌ساز درگاه آزمایشی (local)
     * successUrl/cancelUrl به کال‌بک خود پنل اشاره می‌کنند تا پس از پرداخت،
     * صفحه‌ی «نتیجه پرداخت» (وضعیت + شمارش معکوس) نمایش داده شود و سپس
     * کاربر به callback_url فروشگاه برگردد (مطابق منطق PaymentService).
     *
     * @param  PackagePurchase|SubscriptionRequest  $payable
     */
    private function fakeGatewayData(PackagePurchase|SubscriptionRequest $payable): array
    {
        $callback = route('payment.callback');
        $separator = str_contains($callback, '?') ? '&' : '?';
        $trx = (string) $payable->transaction_id;

        $gateway = \App\Models\Gateway::where('key', 'local')->first();

        $isSubscription = $payable instanceof SubscriptionRequest;

        return [
            'purchase'   => $payable,
            // خرید پکیج → پکیج + طرح قیمت‌گذاری؛ درخواست اشتراک → طرح اشتراک
            'package'    => $isSubscription ? null : $payable->package,
            'plan'       => $isSubscription ? $payable->plan : $payable->pricingPlan,
            'amount'     => $payable->amount,
            'title'      => $gateway?->config('title') ?? 'درگاه پرداخت آزمایشی',
            'subtitle'   => $gateway?->config('description') ?? 'این درگاه فقط برای تست جریان پرداخت است — پول واقعی کم نمی‌شود',
            'payButton'  => $gateway?->config('payButton') ?? 'پرداخت (موفق)',
            'cancelButton' => $gateway?->config('cancelButton') ?? 'لغو پرداخت',
            'successUrl' => $callback . $separator . http_build_query(['transactionId' => $trx]),
            'cancelUrl'  => $callback . $separator . http_build_query(['transactionId' => $trx, 'cancel' => 'true']),
        ];
    }
}
