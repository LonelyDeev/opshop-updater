<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePricingPlan;
use App\Models\PackagePurchase;
use App\Models\PackageVersion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Payment\Invoice;
use Shetabit\Payment\Models\Transaction;

class PaymentService
{
    /**
     * ایجاد تراکنش پرداخت و دریافت payment_url از درگاه
     *
     * @return array{payment_url: string, transaction_id: string, amount: int, gateway: string}
     */
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
            // 1️⃣ ایجاد Invoice
            $invoice = (new Invoice)
                ->amount($purchase->amount)
                ->detail('description', "خرید پکیج {$purchase->package->name} - {$purchase->package->slug}")
                ->detail('purchase_id', $purchase->id)
                ->detail('package_id', $purchase->package_id)
                ->detail('customer_id', $purchase->customer_id)
                ->detail('pricing_plan_id', $purchase->pricing_plan_id);

            // 2️⃣ ایجاد پرداخت با کالبک
            $payment = Payment::callbackUrl($callbackUrl)->purchase($invoice, function ($transaction, $invoice) use ($purchase) {
                // این کالبک بعد از ساخت تراکنش اجرا میشه
                // اطلاعات تراکنش رو در دیتابیس ذخیره میکنیم
                $purchase->update([
                    'transaction_id' => $transaction->transactionId ?? $transaction->id,
                    'gateway'        => $transaction->driver ?? config('payment.default_driver', 'zarinpal'),
                    'payment_url'    => $transaction->getPaymentUrl() ?? null,
                ]);

                Log::info('Purchase transaction created', [
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transaction->transactionId ?? $transaction->id,
                    'gateway' => $transaction->driver ?? 'unknown'
                ]);
            });

            // 3️⃣ دریافت تراکنش و آدرس پرداخت
            $transaction = $payment->getTransaction();
            $paymentUrl = $payment->getPaymentUrl();

            if (!$paymentUrl) {
                throw new RuntimeException('دریافت آدرس پرداخت از درگاه ناموفق بود.');
            }

            // 4️⃣ آپدیت نهایی خرید
            $purchase->update([
                'transaction_id' => $transaction->transactionId ?? $transaction->id,
                'gateway'        => $transaction->driver ?? config('payment.default_driver', 'zarinpal'),
                'payment_url'    => $paymentUrl,
                'status'         => 'pending',
            ]);

            return [
                'payment_url'    => $paymentUrl,
                'transaction_id' => (string) ($transaction->transactionId ?? $transaction->id),
                'amount'         => $purchase->amount,
                'gateway'        => $transaction->driver ?? config('payment.default_driver', 'zarinpal'),
            ];

        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'purchase_id' => $purchase->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString()
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
        $purchase = PackagePurchase::where('transaction_id', $transactionId)
            ->orWhere('id', $transactionId)
            ->first();

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
            // 3️⃣ دریافت تراکنش از دیتابیس Shetabit
            $transaction = Transaction::where('transactionId', $transactionId)
                ->orWhere('id', $transactionId)
                ->first();

            if (!$transaction) {
                throw new RuntimeException('تراکنش درگاه یافت نشد.');
            }

            // 4️⃣ تایید پرداخت
            $receipt = Payment::amount($purchase->amount)
                ->transactionId($transactionId)
                ->verify();

            // 5️⃣ بررسی نتیجه پرداخت
            if ($receipt->isPaid()) {
                // پرداخت موفق
                $purchase->markAsPaid($transaction->driver ?? 'unknown');

                Log::info('Payment verified successfully', [
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transactionId,
                    'gateway' => $transaction->driver ?? 'unknown'
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
                        'gateway'       => $transaction->driver ?? 'unknown',
                        'message'       => 'پرداخت با موفقیت تأیید شد.',
                        'purchase_id'   => $purchase->id,
                    ];

                } catch (\Exception $e) {
                    Log::error('License issuance failed after payment', [
                        'purchase_id' => $purchase->id,
                        'error' => $e->getMessage()
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
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transactionId
                ]);

                return [
                    'paid'    => false,
                    'message' => 'پرداخت تأیید نشد. لطفاً دوباره تلاش کنید.',
                ];
            }

        } catch (\Exception $e) {
            // خطا در تایید پرداخت
            $purchase->markAsFailed($e->getMessage());

            Log::error('Payment verification exception', [
                'transaction_id' => $transactionId,
                'purchase_id' => $purchase->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            'purchase_id' => $purchase->id,
            'transaction_id' => $purchase->transaction_id ?? 'pending'
        ]);
    }

    /**
     * بررسی وضعیت پرداخت
     */
    public function getPaymentStatus(PackagePurchase $purchase): array
    {
        if ($purchase->isPaid()) {
            return [
                'status' => 'paid',
                'message' => 'پرداخت انجام شده است.',
                'license_key' => $purchase->license?->license_key,
                'expires_at' => $purchase->license?->expires_at?->toDateTimeString(),
            ];
        }

        if ($purchase->status === 'failed') {
            return [
                'status' => 'failed',
                'message' => 'پرداخت ناموفق بوده است.',
            ];
        }

        if ($purchase->status === 'pending') {
            return [
                'status' => 'pending',
                'message' => 'پرداخت در انتظار تأیید است.',
                'payment_url' => $purchase->payment_url,
            ];
        }

        return [
            'status' => 'unknown',
            'message' => 'وضعیت پرداخت نامشخص است.',
        ];
    }
}
