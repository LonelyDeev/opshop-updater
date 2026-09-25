<?php

namespace App\Services;

use App\Models\Gateway;
use App\Models\PackagePurchase;
use App\Models\SubscriptionRequest;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class PaymentService
{

    /**
     * ایجاد پرداخت برای خرید پکیج یا درخواست اشتراک
     *
     * @param  PackagePurchase|SubscriptionRequest  $payable
     * @return array{payment_url: string, transaction_id: string, amount: int, gateway: string}
     */
    public function createPayment(PackagePurchase|SubscriptionRequest $payable, ?string $gateway = null): array
    {
        if ($payable->amount <= 0) {
            throw new RuntimeException('مبلغ تراکنش باید بزرگ‌تر از صفر باشد.');
        }

        try {
            // 1️⃣ تنظیمات درگاه (پیش‌فرض: اولین درگاهِ فعال پنل)
            $gateway = $gateway ?? Gateway::where('is_active', true)->first()?->key ?? 'zarinpal';
            $gatewayConfigs = get_gateway_configs($gateway);

            // 2️⃣ آدرس کال‌بک درگاه: کاربر پس از پرداخت ابتدا به صفحه‌ی «نتیجه پرداخت» پنل
            // برمی‌گردد (نمایش وضعیت + شمارش معکسه‌ی ۱۰ ثانیه‌ای) و از همان صفحه به
            // callback_url فروشگاه (که هنگام ایجاد خرید ثبت شده) بازگردانده می‌شود.
            $callbackUrl = URL::route('payment.callback');

            // 3️⃣ ایجاد Invoice (شرح تراکنش بر اساس نوع: خرید پکیج یا طرح اشتراک)
            $invoice = (new Invoice)
                ->amount(intval($payable->amount))
                ->detail('description', $this->payableDescription($payable))
                ->detail($payable instanceof SubscriptionRequest ? 'subscription_request_id' : 'purchase_id', $payable->id)
                ->detail('customer_id', $payable->customer_id);

            // 4️⃣ ایجاد پرداخت
            $payment = Payment::via($gateway)
                ->config($gatewayConfigs)
                ->callbackUrl($callbackUrl)
                ->purchase(
                    $invoice,
                    function ($driver, $transactionId) use ($payable, $gateway) {
                        // نکته: $driver در این نسخه آبجکتِ درایور است نه رشته → کلید درگاه را ذخیره می‌کنیم
                        $payable->update([
                            'transaction_id' => $transactionId,
                            'gateway'        => $gateway,
                        ]);

                        Log::info('Payment transaction created', [
                            'payable'        => $payable instanceof SubscriptionRequest ? 'subscription_request' : 'purchase',
                            'id'             => $payable->id,
                            'transaction_id' => $transactionId,
                            'gateway'        => $gateway,
                        ]);
                    }
                );

            // 5️⃣ اجرای پرداخت و دریافت فرم/آدرس
            // نکته: در این نسخه شتابیت، pay() یک RedirectionForm برمی‌گرداند؛
            // متد getPaymentUrl() وجود ندارد (باگ قبلی: Call to undefined method).
            /** @var \Shetabit\Multipay\RedirectionForm $form */
            $form = $payment->pay();

            $paymentUrl = $this->resolvePaymentUrl($form, $payable, $gateway);

            if (!$paymentUrl) {
                throw new RuntimeException('دریافت آدرس پرداخت از درگاه ناموفق بود.');
            }

            // 6️⃣ آپدیت نهایی (برای اشتراک، وضعیت approval دست‌نخورده می‌ماند = pending)
            $payable->update(
                $payable instanceof SubscriptionRequest
                    ? ['payment_url' => $paymentUrl]
                    : ['payment_url' => $paymentUrl, 'status' => 'pending']
            );

            return [
                'payment_url'    => $paymentUrl,
                'transaction_id' => (string) $payable->transaction_id,
                'amount'         => (int) $payable->amount,
                'gateway'        => $payable->gateway ?? $gateway,
            ];

        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'payable' => $payable instanceof SubscriptionRequest ? 'subscription_request' : 'purchase',
                'id'      => $payable->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('ایجاد تراکنش پرداخت ناموفق بود: ' . $e->getMessage());
        }
    }

    /**
     * شرح فارسی تراکنش برای درگاه (خرید پکیج یا طرح اشتراک)
     */
    private function payableDescription(PackagePurchase|SubscriptionRequest $payable): string
    {
        if ($payable instanceof SubscriptionRequest) {
            return "خرید طرح اشتراک {$payable->plan->name}";
        }

        return "خرید پکیج {$payable->package->name} - {$payable->package->slug}";
    }

    /**
     * تبدیل RedirectionForm شتابیت به payment_url قابل استفاده:
     *
     * ۱) درایورهای URL‌محور (GET بدون فیلد، مثل زرین‌پال):
     *    آدرس اکشن فرم همان payment_url است → مرورگر مستقیم به درگاه می‌رود.
     *
     * ۲) درایورهای فرم‌محور (POST با فیلدها، مثل به‌پرداخت/سامان) و درگاه آزمایشی local:
     *    HTML فرمِ خود-ارسال را در meta خرید ذخیره می‌کنیم و payment_url به مسیر
     *    امضادار payment/form/{purchase} روی پنل اشاره می‌کند؛ مرورگر آنجا فرم را
     *    رندر می‌کند و خودکار به درگاه POST می‌شود. (برای local صفحه شبیه‌ساز درگاه رندر می‌شود.)
     */
    private function resolvePaymentUrl(\Shetabit\Multipay\RedirectionForm $form, PackagePurchase|SubscriptionRequest $payable, string $gateway): string
    {
        $action = trim((string) $form->getAction());
        $method = strtoupper((string) $form->getMethod());
        $inputs = $form->getInputs() ?: [];

        // درایور URL‌محور: فرم GET بدون ورودی → اکشن = لینک پرداخت
        $isUrlDriver = ($method === 'GET' || $method === '') && $action !== '' && empty($inputs);

        if ($isUrlDriver) {
            return $action;
        }

        // درایورهای فرم‌محور: HTML فرم خود-ارسال را ذخیره کن (local در کنترلر صفحه اختصاصی می‌گیرد)
        if ($gateway !== 'local') {
            $payable->forceFill(['meta->payment_form' => $form->render()])->save();
        }

        // پارامتر t=sub برای درخواست‌های اشتراک تا PaymentFormController رکورد درست را پیدا کند
        // (شناسه‌های purchase و subscription_request توالی‌های جداگانه دارند و ممکن است برخورد کنند)
        $extraParams = $payable instanceof SubscriptionRequest ? ['t' => 'sub'] : [];

        return URL::temporarySignedRoute(
            'payment.form',
            now()->addMinutes(30),
            array_merge(['purchase' => $payable->id], $extraParams)
        );
    }

    /**
     * تأیید پرداخت بعد از بازگشت از درگاه (خرید پکیج یا درخواست اشتراک)
     *
     * @return array{paid: bool, license_key: ?string, expires_at: ?string, message: ?string}
     */
    public function verifyPayment(string $transactionId, bool $renew = false): array
    {
        // 0️⃣ درخواست‌های اشتراک: تراکنش‌های طرح‌های اشتراک
        // (پس از تأیید پرداخت، فعال‌سازی با تأیید مدیر انجام می‌شود)
        $subscriptionRequest = SubscriptionRequest::where('transaction_id', $transactionId)->first();

        if ($subscriptionRequest) {
            return $this->verifySubscriptionPayment($subscriptionRequest, $transactionId);
        }

        // 1️⃣ پیدا کردن خرید بر اساس transaction_id
        $purchase = PackagePurchase::where('transaction_id', $transactionId)->first();

        if (!$purchase) {
            Log::warning('Purchase not found for verification', ['transaction_id' => $transactionId]);
            return [
                'paid'    => false,
                'message' => 'تراکنش یافت نشد.',
            ];
        }

        // 2️⃣ اگر قبلاً پرداخت شده
        if ($purchase->isPaid()) {
            Log::info('Purchase already verified', ['purchase_id' => $purchase->id]);
            return [
                'paid'         => true,
                'license_key'  => $purchase->license?->license_key,
                'expires_at'   => $purchase->license?->expires_at?->toDateTimeString(),
                'message'      => 'این تراکنش قبلاً تأیید شده است.',
                'purchase_id'  => $purchase->id,
            ];
        }

        try {
            // 3️⃣ تنظیمات درگاه برای تایید
            $gateway = $purchase->gateway ?? 'zarinpal';
            $gatewayConfigs = get_gateway_configs($gateway);

            // 4️⃣ تایید پرداخت
            // نکته مهم: در شتابیت، خودِِ برگرداندن Receipt یعنی پرداخت موفق بوده؛
            // درگاه ناموفق InvalidPaymentException پرتاب می‌کند که در catch مدیریت می‌شود.
            // (متد isPaid() در این نسخه وجود ندارد.)
            $receipt = Payment::via($gateway)
                ->config($gatewayConfigs)
                ->amount($purchase->amount)
                ->transactionId($transactionId)
                ->verify();

            // 5️⃣ رسید = پرداخت موفق
            // پرداخت موفق
            $purchase->markAsPaid($gateway);

            Log::info('Payment verified successfully', [
                'purchase_id'    => $purchase->id,
                'transaction_id' => $transactionId,
                'gateway'        => $gateway,
            ]);

            // 6️⃣ صدور لایسنس
            try {
                $license = $renew
                    ? app(LicenseService::class)->issueOrRenew($purchase, $purchase->pricingPlan)
                    : app(LicenseService::class)->issueLicense(
                        $purchase,
                        $purchase->pricingPlan
                    );

                return [
                    'paid'          => true,
                    'license_key'   => $license->license_key,
                    'expires_at'    => $license->expires_at?->toDateTimeString(),
                    'transaction_id' => $transactionId,
                    'gateway'       => $gateway,
                    'message'       => 'پرداخت با موفقیت تأیید شد.',
                    'purchase_id'   => $purchase->id,
                ];

            } catch (\Exception $e) {
                Log::error('License issuance failed after payment', [
                    'purchase_id' => $purchase->id,
                    'error'       => $e->getMessage(),
                ]);

                return [
                    'paid'    => true,
                    'message' => 'پرداخت تأیید شد اما صدور لایسنس با خطا مواجه شد. لطفاً با پشتیبانی تماس بگیرید.',
                    'purchase_id' => $purchase->id,
                ];
            }

        } catch (InvalidPaymentException $e) {
            // خطای اختصاصی پرداخت
            $purchase->markAsFailed($e->getMessage());

            Log::error('Invalid payment exception', [
                'transaction_id' => $transactionId,
                'purchase_id'    => $purchase->id ?? null,
                'error'          => $e->getMessage(),
                'code'           => $e->getCode(),
            ]);

            return [
                'paid'    => false,
                'message' => 'تأیید پرداخت ناموفق بود: ' . $e->getMessage(),
            ];

        } catch (\Exception $e) {
            // سایر خطاها
            $purchase->markAsFailed($e->getMessage());

            Log::error('Payment verification exception', [
                'transaction_id' => $transactionId,
                'purchase_id'    => $purchase->id ?? null,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            return [
                'paid'    => false,
                'message' => 'تأیید پرداخت ناموفق بود: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ایجاد لینک کالبک
     */
    public function generateCallbackUrl(PackagePurchase $purchase, ?string $customRoute = null): string
    {
        $route = $customRoute ?? config('packages.payment.callback_route', 'admin.packages.payment.callback');

        return URL::signedRoute($route, [
            'purchase_id'    => $purchase->id,
            'transaction_id' => $purchase->transaction_id ?? 'pending',
        ]);
    }

    /**
     * بررسی وضعیت پرداخت
     */
    public function getPaymentStatus(PackagePurchase $purchase): array
    {
        if ($purchase->isPaid()) {
            return [
                'status'      => 'paid',
                'message'     => 'پرداخت انجام شده است.',
                'license_key' => $purchase->license?->license_key,
                'expires_at'  => $purchase->license?->expires_at?->toDateTimeString(),
            ];
        }

        if ($purchase->status === 'failed') {
            return [
                'status'  => 'failed',
                'message' => 'پرداخت ناموفق بوده است.',
            ];
        }

        if ($purchase->status === 'pending') {
            return [
                'status'      => 'pending',
                'message'     => 'پرداخت در انتظار تأیید است.',
                'payment_url' => $purchase->payment_url,
            ];
        }

        return [
            'status'  => 'unknown',
            'message' => 'وضعیت پرداخت نامشخص است.',
        ];
    }

    /**
     * تأیید پرداخت درخواست اشتراک:
     * رسید = پرداخت موفق → درخواست payment_status=paid و همچنان در انتظار تأیید مدیر.
     * (صدور لایسنس اینجا انجام نمی‌شود — فعال‌سازی پس از تأیید مدیر است.)
     */
    private function verifySubscriptionPayment(SubscriptionRequest $request, string $transactionId): array
    {
        if ($request->isPaid()) {
            Log::info('Subscription payment already verified', ['request_id' => $request->id]);

            return [
                'paid'                => true,
                'subscription_request'=> true,
                'request_id'          => $request->id,
                'status'              => $request->status,
                'message'             => 'این تراکنش قبلاً تأیید شده است.',
            ];
        }

        try {
            $gateway = $request->gateway ?? 'zarinpal';
            $gatewayConfigs = get_gateway_configs($gateway);

            // خودِ رسید = موفق؛ درگاه ناموفق InvalidPaymentException پرتاب می‌کند
            Payment::via($gateway)
                ->config($gatewayConfigs)
                ->amount($request->amount)
                ->transactionId($transactionId)
                ->verify();

            app(SubscriptionService::class)->settlePayment($request, $gateway);

            return [
                'paid'                => true,
                'subscription_request'=> true,
                'request_id'          => $request->id,
                'status'              => $request->status,
                'message'             => 'پرداخت با موفقیت تأیید شد. درخواست شما در انتظار تأیید مدیر است.',
            ];

        } catch (InvalidPaymentException $e) {
            $request->markPaymentFailed($e->getMessage());

            Log::error('Invalid subscription payment', [
                'transaction_id' => $transactionId,
                'request_id'     => $request->id,
                'error'          => $e->getMessage(),
            ]);

            return [
                'paid'                => false,
                'subscription_request'=> true,
                'request_id'          => $request->id,
                'status'              => $request->status,
                'message'             => 'تأیید پرداخت ناموفق بود: ' . $e->getMessage(),
            ];

        } catch (\Exception $e) {
            $request->markPaymentFailed($e->getMessage());

            Log::error('Subscription payment verification exception', [
                'transaction_id' => $transactionId,
                'request_id'     => $request->id,
                'error'          => $e->getMessage(),
            ]);

            return [
                'paid'                => false,
                'subscription_request'=> true,
                'request_id'          => $request->id,
                'status'              => $request->status,
                'message'             => 'تأیید پرداخت ناموفق بود: ' . $e->getMessage(),
            ];
        }
    }
}
