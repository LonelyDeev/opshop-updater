<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageLicense;
use App\Models\PackagePricingPlan;
use App\Models\PackagePurchase;
use App\Models\PackageVersion;
use App\Models\SubscriptionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use packages\shetabit\payment\src\Facade\Payment;
use Shetabit\Payment\Models\Transaction;

class LicenseService
{
    /**
     * صدور لایسنس پس از پرداخت موفق
     */
    public function issueLicense(PackagePurchase $purchase,PackagePricingPlan $plan): PackageLicense {

        return DB::transaction(function () use ($purchase, $plan) {
            $startsAt = now();
            $expiresAt = $plan->duration_months > 0
                ? Carbon::now()->addMonths($plan->duration_months)
                : null;

            $license = PackageLicense::create([
                'license_key'     => PackageLicense::generateKey(),
                'package_id'      => $purchase->package_id,
                'customer_id'     => $purchase->customer_id,
                'purchase_id'     => $purchase->id,
                'status'          => PackageLicense::STATUS_ACTIVE,
                'starts_at'       => $startsAt,
                'expires_at'      => $expiresAt,
                'duration_months' => $plan->duration_months,
            ]);

            $purchase->update(['license_id' => $license->id]);
            $purchase->package->incrementPurchases();

            return $license;
        });
    }

    /**
     * صدور یا تمدید لایسنس بر اساس وضعیت فعلی مشتری:
     * اگر مشتری برای همین پکیج لایسنس فعال/منقضی (غیر باطل‌شده) داشته باشد
     * ابتدا تمدید انجام می‌شود؛ در صورت خطا (طرح‌های یک‌بار مصرف) یا نبود لایسنس قبلی، لایسنس جدید صادر می‌شود.
     */
    public function issueOrRenew(PackagePurchase $purchase, PackagePricingPlan $plan): PackageLicense
    {
        $oldLicense = PackageLicense::query()
            ->where('package_id', $purchase->package_id)
            ->where('customer_id', $purchase->customer_id)
            ->whereIn('status', [PackageLicense::STATUS_ACTIVE, PackageLicense::STATUS_EXPIRED])
            ->latest('id')
            ->first();

        if ($oldLicense) {
            try {
                return $this->renewLicense($oldLicense, $purchase, $plan);
            } catch (\RuntimeException $e) {
                // طرح یک‌بار مصرف قابل تمدید نیست → لایسنس جدید صادر می‌کنیم
            }
        }

        return $this->issueLicense($purchase, $plan);
    }

    /**
     * تمدید لایسنس (برای لایسنس‌های منقضی یا در حال انقضا)
     * لایسنس جدید ساخته می‌شود و renewed_from به لایسنس قبلی اشاره می‌کند
     */
    public function renewLicense(PackageLicense $oldLicense,PackagePurchase $newPurchase,PackagePricingPlan $plan): PackageLicense {
        // جلوگیری از تمدید طرح‌های یک‌بار مصرف
        if ($plan->is_one_time) {
            throw new \RuntimeException(
                'این طرح یک‌بار مصرف است و قابل تمدید نیست. لطفاً طرح دیگری انتخاب کنید.'
            );
        }

        // اگر لایسنس قبلی از یک طرح one-time بوده، جلوگیری از تمدید
        $oldPlan = $oldLicense->purchase?->pricingPlan;
        if ($oldPlan && $oldPlan->is_one_time) {
            throw new \RuntimeException(
                'لایسنس فعلی شما از طرح یک‌بار مصرف است و قابل تمدید نیست. لطفاً طرح دیگری انتخاب کنید.'
            );
        }

        return DB::transaction(function () use ($oldLicense, $newPurchase, $plan) {
            // اگر لایسنس قبلی هنوز فعال است، تاریخ انقضا را اضافه می‌کنیم
            // در غیر این صورت، از الان شروع می‌شود
            $startsAt = $oldLicense->isActive() ? $oldLicense->starts_at : now();
            $baseDate = $oldLicense->isActive() && $oldLicense->expires_at
                ? $oldLicense->expires_at
                : now();

            $expiresAt = $plan->duration_months > 0
                ? (clone $baseDate)->addMonths($plan->duration_months)
                : null;

            $license = PackageLicense::create([
                'license_key'     => PackageLicense::generateKey(),
                'package_id'      => $oldLicense->package_id,
                'customer_id'     => $oldLicense->customer_id,
                'purchase_id'     => $newPurchase->id,
                'renewed_from'    => $oldLicense->id,
                'status'          => PackageLicense::STATUS_ACTIVE,
                'starts_at'       => $startsAt,
                'expires_at'      => $expiresAt,
                'duration_months' => $plan->duration_months,
            ]);

            // لایسنس قبلی را revoked می‌کنیم
            $oldLicense->update(['status' => PackageLicense::STATUS_REVOKED]);

            $newPurchase->update(['license_id' => $license->id]);

            return $license;
        });
    }

    /**
     * بررسی اعتبار لایسنس
     * Returns: ['valid' => bool, 'expires_at' => ?string, 'days_remaining' => ?int, 'version' => ?string]
     */
    public function verify(PackageLicense $license): array
    {
        if (!$license->isActive()) {
            return [
                'valid'         => false,
                'message'       => 'لایسنس غیرفعال یا منقضی است.',
                'expires_at'    => $license->expires_at?->toDateTimeString(),
                'days_remaining'=> $license->days_remaining,
            ];
        }

        $latestVersion = $license->package->latestVersion();

        return [
            'valid'         => true,
            'expires_at'    => $license->expires_at?->toDateTimeString(),
            'days_remaining'=> $license->days_remaining,
            'version'       => $latestVersion ? $latestVersion->first()->version : null,
            'is_unlimited'  => $license->isUnlimited(),
        ];
    }

    /**
     * مارک کردن لایسنس‌های منقضی‌شده به‌صورت خودکار
     */
    public function expireOldLicenses(): int
    {
        $count = PackageLicense::where('status', PackageLicense::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => PackageLicense::STATUS_EXPIRED]);

        Log::info("Expired {$count} licenses");
        return $count;
    }

    /* ===================================================================
     *  اشتراک‌ها (Subscription Plans)
     * =================================================================== */

    /**
     * صدور یا تمدید لایسنس برای یک پکیجِ داخل طرح اشتراک (پس از تأیید مدیر):
     *
     *  - اگر مشتری برای این پکیج لایسنس فعال/منقضی داشته باشد → تمدید
     *    (انقضا جدید = انقضای فعلی + مدت طرح؛ لایسنس قبلی revoked می‌شود)
     *  - در غیر این صورت → لایسنس جدید با مدت طرح
     *  - duration = 0 → لایسنس نامحدود
     *
     * @param  int|null  $durationMonths  مدت (ماه)؛ null = مدت پیش‌فرض طرح
     */
    public function issueOrRenewForSubscription(
        Customer $customer,
        Package $package,
        ?int $durationMonths,
        SubscriptionRequest $request
    ): PackageLicense {
        $duration = (int) ($durationMonths ?? $request->plan->duration_months);

        $oldLicense = PackageLicense::query()
            ->where('package_id', $package->id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', [PackageLicense::STATUS_ACTIVE, PackageLicense::STATUS_EXPIRED])
            ->latest('id')
            ->first();

        if ($oldLicense && $oldLicense->isActive()) {
            return $this->renewForSubscription($oldLicense, $duration, $request);
        }

        return $this->issueForSubscription($customer, $package, $duration, $request);
    }

    private function issueForSubscription(
        Customer $customer,
        Package $package,
        int $duration,
        SubscriptionRequest $request
    ): PackageLicense {
        return PackageLicense::create([
            'license_key'              => PackageLicense::generateKey(),
            'package_id'               => $package->id,
            'customer_id'              => $customer->id,
            'subscription_request_id'  => $request->id,
            'status'                   => PackageLicense::STATUS_ACTIVE,
            'starts_at'                => now(),
            'expires_at'               => $duration > 0 ? Carbon::now()->addMonths($duration) : null,
            'duration_months'          => $duration,
            'notes'                    => "صدور از طریق طرح اشتراک «{$request->plan->name}»",
        ]);
    }

    private function renewForSubscription(
        PackageLicense $oldLicense,
        int $duration,
        SubscriptionRequest $request
    ): PackageLicense {
        $license = PackageLicense::create([
            'license_key'              => PackageLicense::generateKey(),
            'package_id'               => $oldLicense->package_id,
            'customer_id'              => $oldLicense->customer_id,
            'subscription_request_id'  => $request->id,
            'renewed_from'             => $oldLicense->id,
            'status'                   => PackageLicense::STATUS_ACTIVE,
            'starts_at'                => $oldLicense->starts_at ?? now(),
            // تمدید از انقضای فعلی؛ مدت ۰ = نامحدود
            'expires_at'               => $duration > 0
                ? (clone ($oldLicense->expires_at ?? now()))->addMonths($duration)
                : null,
            'duration_months'          => $duration,
            'notes'                    => "تمدید از طریق طرح اشتراک «{$request->plan->name}»",
        ]);

        $oldLicense->update(['status' => PackageLicense::STATUS_REVOKED]);

        return $license;
    }
}
