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
            $payment = Payment::callbackUrl($callbackUrl)
                ->amount($purchase->amount)
                ->detail('description', "خرید پکیج {$purchase->package->name} - {$purchase->package->slug}");

            $transaction = $payment->purchase(
                function (Transaction $tx) use ($purchase) {
                    $tx->payload([
                        'purchase_id' => $purchase->id,
                        'package_id'  => $purchase->package_id,
                        'customer_id' => $purchase->customer_id,
                    ]);
                }
            )->pay();

            // ذخیره transaction_id در purchase
            $purchase->update([
                'transaction_id' => $transaction->transactionId ?? $transaction->id,
                'gateway'        => $transaction->driver,
                'payment_url'    => method_exists($transaction, 'getPaymentUrl')
                    ? $transaction->getPaymentUrl()
                    : null,
            ]);

            $paymentUrl = method_exists($transaction, 'getPaymentUrl')
                ? $transaction->getPaymentUrl()
                : null;

            if (!$paymentUrl) {
                throw new RuntimeException('دریافت آدرس پرداخت از درگاه ناموفق بود.');
            }

            return [
                'payment_url'    => $paymentUrl,
                'transaction_id' => (string) ($transaction->transactionId ?? $transaction->id),
                'amount'         => $purchase->amount,
                'gateway'        => $transaction->driver,
            ];
        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'purchase_id' => $purchase->id,
                'error'       => $e->getMessage(),
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
        $purchase = PackagePurchase::where('transaction_id', $transactionId)
            ->orWhere('id', $transactionId)
            ->first();

        if (!$purchase) {
            return [
                'paid'   => false,
                'message' => 'تراکنش یافت نشد.',
            ];
        }

        if ($purchase->isPaid()) {
            // قبلاً پرداخت شده - اطلاعات موجود را برمی‌گردانیم
            return [
                'paid'        => true,
                'license_key' => $purchase->license?->license_key,
                'expires_at'  => $purchase->license?->expires_at?->toDateTimeString(),
                'message'     => 'این تراکنش قبلاً تأیید شده است.',
            ];
        }

        try {
            // دریافت تراکنش shetabit
            $transaction = Transaction::where('transactionId', $transactionId)
                ->orWhere('id', $transactionId)
                ->first();

            if (!$transaction) {
                throw new RuntimeException('تراکنش درگاه یافت نشد.');
            }

            $receipt = Payment::amount($purchase->amount)->transaction($transaction)->verify();

            // پرداخت موفق
            $purchase->markAsPaid($transaction->driver);

            // صدور لایسنس
            $license = app(LicenseService::class)->issueLicense(
                $purchase,
                $purchase->pricingPlan
            );

            return [
                'paid'         => true,
                'license_key'  => $license->license_key,
                'expires_at'   => $license->expires_at?->toDateTimeString(),
                'transaction_id' => $transactionId,
                'gateway'      => $transaction->driver,
                'message'      => 'پرداخت با موفقیت تأیید شد.',
            ];
        } catch (\Exception $e) {
            $purchase->markAsFailed($e->getMessage());

            Log::error('Payment verification failed', [
                'transaction' => $transactionId,
                'error'       => $e->getMessage(),
            ]);

            return [
                'paid'   => false,
                'message' => 'تأیید پرداخت ناموفق بود: ' . $e->getMessage(),
            ];
        }
    }
}
