<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PackageLicense;
use Illuminate\Http\Request;
use RuntimeException;

class PackageApiAuthService
{
    /**
     * احراز هویت درخواست API با update_code مشتری و دامنه‌ی درخواست‌کننده
     *
     * @return Customer
     * @throws RuntimeException
     */
    public function authenticate(Request $request): Customer
    {
        // 1) دریافت توکن - از header یا query
        $token = $request->bearerToken()
            ?? $request->header('X-Project-Key')
            ?? $request->input('token');

        if (!$token) {
            throw new RuntimeException('توکن احراز هویت ارسال نشده است.', 403);
        }

        // 2) یافتن مشتری
        $customer = Customer::where('update_code', $token)->first();

        if (!$customer) {
            throw new RuntimeException('مشتری نامعتبر است.', 403);
        }

        if ($customer->status !== 'active') {
            throw new RuntimeException('حساب مشتری غیرفعال است.', 403);
        }

        // 3) بررسی دامنه
        $allowedDomain = $customer->website_url;
        $requestDomain = $request->getHost();
        $requestFullDomain = $request->getHttpHost();

        if (!$this->isDomainAllowed($allowedDomain, $requestFullDomain, $requestDomain)) {
            throw new RuntimeException(
                "دسترسی از دامنه {$requestFullDomain} مجاز نیست.",
                403
            );
        }

        return $customer;
    }

    /**
     * بررسی تطابق دامنه‌ی درخواست با دامنه‌ی مجاز مشتری
     */
    public function isDomainAllowed(?string $allowed, string $requestFull, string $requestHost): bool
    {
        if (!$allowed) {
            return false;
        }

        // نرمال‌سازی - حذف http:// و https:// و www
        $allowed = preg_replace('#^https?://#', '', $allowed);
        $allowed = preg_replace('#^www\.#', '', $allowed);
        $allowed = trim($allowed, '/');

        $requestFull = preg_replace('#^www\.#', '', $requestFull);
        $requestHost = preg_replace('#^www\.#', '', $requestHost);

        // تطابق دقیق یا زیردامنه
        return $allowed === $requestFull
            || $allowed === $requestHost
            || str_ends_with($requestHost, '.' . $allowed);
    }

    /**
     * دریافت لایسنس فعال مشتری برای یک پکیج
     */
    public function getActiveLicense(Customer $customer, string $packageSlug): ?PackageLicense
    {
        return PackageLicense::where('customer_id', $customer->id)
            ->where('status', PackageLicense::STATUS_ACTIVE)
            ->whereHas('package', fn ($q) => $q->where('slug', $packageSlug))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
