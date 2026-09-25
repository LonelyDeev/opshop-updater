<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * سرویس طرح‌های اشتراک:
 *
 *  - ثبت درخواست خرید طرح (پولی/رایگان) از فرانت یا API
 *  - تأیید پرداخت درخواست (بازگشت از درگاه)
 *  - تأیید مدیر → فعال‌سازی: صدور/تمدید لایسنس همه پکیج‌های طرح
 *  - رد درخواست با یادداشت مدیر
 */
class SubscriptionService
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * ثبت درخواست خرید طرح
     *
     * @param  string|null  $callbackUrl  برای خریدهای API (بازگشت به فروشگاه بیرونی)
     * @return array{request: SubscriptionRequest, payment_url: ?string}
     *
     * @throws RuntimeException پیام‌های فارسی برای UI
     */
    public function createRequest(
        Customer $customer,
        SubscriptionPlan $plan,
        ?string $gateway = null,
        ?string $callbackUrl = null
    ): array {
        if (!$plan->is_active) {
            throw new RuntimeException('این طرح غیرفعال است.');
        }

        if ($plan->hasCustomerUsed($customer->id)) {
            throw new RuntimeException(
                'شما قبلاً از این طرح استفاده کرده‌اید. این طرح فقط یک‌بار قابل خریداری است.'
            );
        }

        if ($plan->hasCustomerPending($customer->id)) {
            throw new RuntimeException(
                'شما یک درخواست در جریان برای این طرح دارید که در انتظار تأیید مدیر است.'
            );
        }

        if ($plan->packages()->count() === 0) {
            throw new RuntimeException('این طرح هنوز پکیجی ندارد.');
        }

        $isFree = $plan->final_price <= 0;

        $request = SubscriptionRequest::create([
            'customer_id'           => $customer->id,
            'subscription_plan_id'  => $plan->id,
            'amount'                => $plan->final_price,
            'payment_status'        => $isFree ? SubscriptionRequest::PAYMENT_FREE : SubscriptionRequest::PAYMENT_PENDING,
            'status'                => SubscriptionRequest::STATUS_PENDING,
            'gateway'               => $isFree ? null : $gateway,
            'callback_url'          => $callbackUrl,
        ]);

        // طرح رایگان → نیازی به پرداخت نیست؛ مستقیم در انتظار تأیید مدیر
        if ($isFree) {
            return ['request' => $request, 'payment_url' => null];
        }

        // طرح پولی → ایجاد تراکنش درگاه
        try {
            $payment = $this->paymentService->createPayment($request, $gateway);

            return ['request' => $request, 'payment_url' => $payment['payment_url']];
        } catch (\Exception $e) {
            // اگر درگاه تراکنش نسازد، رکورد یتیم باقی نماند
            $request->delete();
            throw $e;
        }
    }

    /**
     * تأیید پرداخت درخواست اشتراک (پرداخت موفق → در انتظار تأیید مدیر)
     * خودِ تایید درگاه داخل PaymentService::verifyPayment انجام می‌شود و
     * سپس این متد فراخوانی می‌شود تا وضعیت درخواست به‌روز شود.
     */
    public function settlePayment(SubscriptionRequest $request, ?string $gateway = null): void
    {
        if ($request->isPaid()) {
            return;
        }

        $request->markAsPaid($gateway ?? $request->gateway);

        Log::info('Subscription payment verified', [
            'request_id'     => $request->id,
            'plan'           => $request->plan->name,
            'customer'       => $request->customer->name,
            'transaction_id' => $request->transaction_id,
        ]);
    }

    /**
     * تأیید مدیر + فعال‌سازی: برای همه پکیج‌های طرح، لایسنس صادر/تمدید می‌شود.
     *
     * @return PackageLicense[] لایسنس‌های صادرشده
     */
    public function approve(SubscriptionRequest $request, ?string $note = null): array
    {
        if ($request->status !== SubscriptionRequest::STATUS_PENDING) {
            throw new RuntimeException('این درخواست قبلاً بررسی شده است.');
        }

        if (!$request->isPaymentSettled()) {
            throw new RuntimeException('پرداخت این درخواست هنوز انجام نشده است.');
        }

        $licenses = DB::transaction(function () use ($request, $note) {
            $issued = [];

            foreach ($request->plan->packages()->get() as $package) {
                $issued[] = app(LicenseService::class)->issueOrRenewForSubscription(
                    $request->customer,
                    $package,
                    $package->pivot->duration_months,
                    $request
                );
            }

            $activated = array_map(fn ($l) => [
                'package'    => $l->package?->name,
                'package_id' => $l->package_id,
                'license_key'=> $l->license_key,
                'expires_at' => $l->expires_at?->toDateTimeString(),
            ], $issued);

            $request->update([
                'status'      => SubscriptionRequest::STATUS_APPROVED,
                'approved_at' => now(),
                'admin_note'  => $note ?: $request->admin_note,
                'meta'        => array_merge($request->meta ?? [], ['activated_licenses' => $activated]),
            ]);

            Log::info('Subscription request approved', [
                'request_id' => $request->id,
                'plan'       => $request->plan->name,
                'customer'   => $request->customer->name,
                'licenses'   => count($issued),
            ]);

            return $issued;
        });

        return $licenses;
    }

    /**
     * رد درخواست توسط مدیر (با یادداشت اختیاری)
     */
    public function reject(SubscriptionRequest $request, ?string $note = null): void
    {
        if ($request->status !== SubscriptionRequest::STATUS_PENDING) {
            throw new RuntimeException('این درخواست قبلاً بررسی شده است.');
        }

        $request->update([
            'status'      => SubscriptionRequest::STATUS_REJECTED,
            'rejected_at' => now(),
            'admin_note'  => $note,
        ]);

        Log::info('Subscription request rejected', [
            'request_id' => $request->id,
            'plan'       => $request->plan->name,
            'customer'   => $request->customer->name,
        ]);
    }
}
