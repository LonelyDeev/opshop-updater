<?php

namespace App\Services;

use App\Models\PackagePurchase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class PaymentService
{

    public function createPayment(PackagePurchase $purchase): array
    {
        if ($purchase->amount <= 0) {
            throw new RuntimeException('مبلغ تراکنش باید بزرگ‌تر از صفر باشد.');
        }

        $callbackUrl = $purchase->callback_url;
        if (!$callbackUrl) {
            throw new RuntimeException('callback_url تنظیم نشده است.');
        }
        try {
            // 1️⃣ تنظیمات درگاه
            $gateway = 'zarinpal';
            $gatewayConfigs = get_gateway_configs($gateway);

            // 2️⃣ ایجاد Invoice
            $invoice = (new Invoice)
                ->amount(intval($purchase->amount))
                ->detail('description', "خرید پکیج {$purchase->package->name} - {$purchase->package->slug}")
                ->detail('purchase_id', $purchase->id)
                ->detail('package_id', $purchase->package_id)
                ->detail('customer_id', $purchase->customer_id)
                ->detail('pricing_plan_id', $purchase->pricing_plan_id);

            // 3️⃣ ایجاد پرداخت
            $payment = Payment::via($gateway)
                ->config($gatewayConfigs)
                ->callbackUrl($callbackUrl)
                ->purchase(
                    $invoice,
                    function ($driver, $transactionId) use ($purchase, $gateway) {
                        $purchase->update([
                            'transaction_id' => $transactionId,
                            'gateway'        => $driver,
                        ]);

                        Log::info('Purchase transaction created', [
                            'purchase_id'   => $purchase->id,
                            'transaction_id' => $transactionId,
                            'gateway'       => $driver,
                        ]);
                    }
                );

            // 4️⃣ اجرای پرداخت و دریافت آدرس
            $payment->pay();
            $paymentUrl = $payment->getPaymentUrl();

            if (!$paymentUrl) {
                throw new RuntimeException('دریافت آدرس پرداخت از درگاه ناموفق بود.');
            }

            // 5️⃣ آپدیت نهایی
            $purchase->update([
                'payment_url' => $paymentUrl,
                'status'      => 'pending',
            ]);

            return [
                'payment_url'    => $paymentUrl,
                'transaction_id' => (string) $purchase->transaction_id,
                'amount'         => $purchase->amount,
                'gateway'        => $purchase->gateway ?? $gateway,
            ];

        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'purchase_id' => $purchase->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            throw new RuntimeException('ایجاد تراکنش پرداخت ناموفق بود: ' . $e->getMessage());
        }
    }

    /**
     * تأیید پرداخت بعد از بازگشت از درگاه
     *
     * @return array{paid: bool, license_key: ?string, expires_at: ?string, message: ?string}
     */
    public function verifyPayment(string $transactionId): array
    {
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
            $receipt = Payment::via($gateway)
                ->config($gatewayConfigs)
                ->amount($purchase->amount)
                ->transactionId($transactionId)
                ->verify();

            // 5️⃣ بررسی نتیجه پرداخت
            if ($receipt->isPaid()) {
                // پرداخت موفق
                $purchase->markAsPaid($gateway);

                Log::info('Payment verified successfully', [
                    'purchase_id'    => $purchase->id,
                    'transaction_id' => $transactionId,
                    'gateway'        => $gateway,
                ]);

                // 6️⃣ صدور لایسنس
                try {
                    $license = app(LicenseService::class)->issueLicense(
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

            } else {
                // پرداخت ناموفق
                $purchase->markAsFailed('پرداخت تأیید نشد');

                Log::warning('Payment verification failed - not paid', [
                    'purchase_id'    => $purchase->id,
                    'transaction_id' => $transactionId,
                ]);

                return [
                    'paid'    => false,
                    'message' => 'پرداخت تأیید نشد. لطفاً دوباره تلاش کنید.',
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
}
