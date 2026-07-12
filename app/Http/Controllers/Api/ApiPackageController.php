<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePricingPlan;
use App\Models\PackagePurchase;
use App\Services\LicenseService;
use App\Services\PackageApiAuthService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ApiPackageController extends Controller
{
    public function __construct(
        private PackageApiAuthService $authService,
        private PaymentService $paymentService,
        private LicenseService $licenseService
    ) {}

    /* ===================================================================
     *  GET /api/v1/packages
     *  لیست پکیج‌های قابل دسترس برای این مشتری (پکیج‌های پروژه‌ای که مشتری اشتراک دارد)
     * =================================================================== */
    public function index(Request $request): JsonResponse
    {
        try {
            $customer = $this->authService->authenticate($request);

            // پکیج‌های مرتبط با پروژه‌های این مشتری
            $projectIds = $customer->subscriptions()
                ->where('status', 'active')
                ->where('payment_status', 'paid')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('project_id')
                ->unique();

            $packages = Package::with(['latestVersion', 'activePricingPlans', 'images'])
                ->whereIn('project_id', $projectIds)
                ->where('status', Package::STATUS_ACTIVE)
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(fn ($sq) => $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%"));
                })
                ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'data' => $packages->items(),
                'meta' => [
                    'current_page' => $packages->currentPage(),
                    'last_page'    => $packages->lastPage(),
                    'total'        => $packages->total(),
                    'per_page'     => $packages->perPage(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    /* ===================================================================
     *  GET /api/v1/packages/{slug}
     *  جزئیات پکیج
     * =================================================================== */
    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $customer = $this->authService->authenticate($request);

            $package = Package::with(['activeVersions', 'activePricingPlans', 'project', 'images'])
                ->where('slug', $slug)
                ->where('status', Package::STATUS_ACTIVE)
                ->first();

            if (!$package) {
                return response()->json(['error' => 'پکیج یافت نشد.'], 404);
            }

            // بررسی دسترسی مشتری به پروژه‌ی این پکیج
            $hasAccess = $customer->subscriptions()
                ->where('project_id', $package->project_id)
                ->where('status', 'active')
                ->where('payment_status', 'paid')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (!$hasAccess) {
                return response()->json(['error' => 'دسترسی غیرمجاز.'], 403);
            }

            // اگر لایسنس فعلی دارد، اطلاعاتش رو برمی‌گردانیم
            $license = $this->authService->getActiveLicense($customer, $slug);

            return response()->json([
                'data' => array_merge(
                    $package->toArray(),
                    [
                        'installed_license' => $license ? [
                            'license_key'    => $license->license_key,
                            'expires_at'     => $license->expires_at?->toDateTimeString(),
                            'days_remaining' => $license->days_remaining,
                        ] : null,
                    ]
                ),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    /* ===================================================================
     *  POST /api/v1/packages/{slug}/purchase
     *  ایجاد درخواست خرید و دریافت payment_url
     *  Body: { callback_url, pricing_plan_id }
     *  Response: { payment_url, transaction_id, amount, gateway }
     * =================================================================== */
    public function purchase(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'callback_url'    => 'required|url',
            'pricing_plan_id' => 'required|exists:package_pricing_plans,id',
        ]);

        try {
            $customer = $this->authService->authenticate($request);

            $package = Package::where('slug', $slug)
                ->where('status', Package::STATUS_ACTIVE)
                ->first();

            if (!$package) {
                return response()->json(['error' => 'پکیج یافت نشد.'], 404);
            }

            $plan = PackagePricingPlan::where('id', $request->pricing_plan_id)
                ->where('package_id', $package->id)
                ->where('is_active', true)
                ->first();

            if (!$plan) {
                return response()->json(['error' => 'طرح قیمت‌گذاری نامعتبر است.'], 422);
            }

            // جلوگیری از خرید مجدد طرح‌های یک‌بار مصرف
            if ($plan->is_one_time && $plan->hasCustomerUsed($customer->id)) {
                return response()->json([
                    'error' => 'شما قبلاً از این طرح استفاده کرده‌اید. این طرح فقط یک‌بار قابل خریداری است. لطفاً طرح دیگری انتخاب کنید.',
                ], 422);
            }

            $latestVersion = $package->latestVersion()->first();

            if (!$latestVersion) {
                return response()->json(['error' => 'نسخه فعالی برای این پکیج وجود ندارد.'], 422);
            }

            // اگر پکیج رایگان است، نیازی به پرداخت نیست - مستقیم لایسنس صادر می‌شود
            if ($package->is_free || $plan->final_price <= 0) {
                $purchase = PackagePurchase::create([
                    'package_id'      => $package->id,
                    'version_id'      => $latestVersion->id,
                    'pricing_plan_id' => $plan->id,
                    'customer_id'     => $customer->id,
                    'amount'          => 0,
                    'status'          => PackagePurchase::STATUS_PAID,
                    'paid_at'         => now(),
                ]);

                $license = $this->licenseService->issueLicense($purchase, $plan);

                return response()->json([
                    'is_free'      => true,
                    'license_key'  => $license->license_key,
                    'expires_at'   => $license->expires_at?->toDateTimeString(),
                    'download_token' => $this->createDownloadToken($license, $latestVersion, $customer),
                ]);
            }

            // ایجاد رکورد purchase
            $purchase = PackagePurchase::create([
                'package_id'      => $package->id,
                'version_id'      => $latestVersion->id,
                'pricing_plan_id' => $plan->id,
                'customer_id'     => $customer->id,
                'callback_url'    => $request->callback_url,
                'amount'          => $plan->final_price,
                'status'          => PackagePurchase::STATUS_PENDING,
            ]);

            // ایجاد پرداخت در درگاه
            $payment = $this->paymentService->createPayment($purchase);

            return response()->json([
                'payment_url'    => $payment['payment_url'],
                'transaction_id' => $payment['transaction_id'],
                'amount'         => $payment['amount'],
                'gateway'        => $payment['gateway'],
                'purchase_id'    => $purchase->id,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /* ===================================================================
     *  POST /api/v1/payments/{transaction_id}/verify
     *  تأیید پرداخت توسط پروژه خریدار
     *  Response: { paid, license_key, expires_at, download_token }
     * =================================================================== */
    public function verifyPayment(Request $request, string $transactionId): JsonResponse
    {
        try {
            $customer = $this->authService->authenticate($request);

            $purchase = PackagePurchase::where('transaction_id', $transactionId)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$purchase) {
                return response()->json([
                    'paid'   => false,
                    'error'  => 'تراکنش یافت نشد.',
                ], 404);
            }

            // اگر قبلاً پرداخت و لایسنس صادر شده
            if ($purchase->isPaid() && $purchase->license) {
                $license = $purchase->license;
                $latestVersion = $purchase->package->latestVersion();

                return response()->json([
                    'paid'         => true,
                    'license_key'  => $license->license_key,
                    'expires_at'   => $license->expires_at?->toDateTimeString(),
                    'days_remaining' => $license->days_remaining,
                    'download_token' => $latestVersion
                        ? $this->createDownloadToken($license, $latestVersion, $customer)
                        : null,
                    'signature'    => $latestVersion?->file_hash,
                    'version'      => $latestVersion?->version,
                ]);
            }

            // اگر هنوز pending است، تلاش به تأیید درگاه
            if ($purchase->status === PackagePurchase::STATUS_PENDING) {
                $result = $this->paymentService->verifyPayment($transactionId);

                if ($result['paid'] ?? false) {
                    $purchase->refresh();
                    $license = $purchase->license;
                    $latestVersion = $purchase->package->latestVersion();

                    return response()->json([
                        'paid'         => true,
                        'license_key'  => $license?->license_key,
                        'expires_at'   => $license?->expires_at?->toDateTimeString(),
                        'days_remaining' => $license?->days_remaining,
                        'download_token' => ($license && $latestVersion)
                            ? $this->createDownloadToken($license, $latestVersion, $customer)
                            : null,
                        'signature'    => $latestVersion?->file_hash,
                        'version'      => $latestVersion?->version,
                    ]);
                }

                return response()->json([
                    'paid'   => false,
                    'error'  => $result['message'] ?? 'پرداخت هنوز تأیید نشده است.',
                ], 400);
            }

            return response()->json([
                'paid'   => false,
                'error'  => 'وضعیت تراکنش: ' . $purchase->status,
            ], 400);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /* ===================================================================
     *  POST /api/v1/packages/{slug}/verify-license
     *  بررسی اعتبار لایسنس
     *  Body: { license_key }
     *  Response: { valid, expires_at, days_remaining, version, download_token, signature }
     * =================================================================== */
    public function verifyLicense(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        try {
            $customer = $this->authService->authenticate($request);

            $package = Package::where('slug', $slug)->first();
            if (!$package) {
                return response()->json(['valid' => false, 'message' => 'پکیج یافت نشد.'], 404);
            }

            $license = \App\Models\PackageLicense::where('license_key', $request->license_key)
                ->where('package_id', $package->id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$license) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'لایسنس متعلق به این مشتری نیست.',
                ], 403);
            }

            $result = $this->licenseService->verify($license);

            if (!$result['valid']) {
                // اگه لایسنس منقضی شده و از طرح one-time بوده، پیام واضح بده
                $plan = $license->purchase?->pricingPlan;
                if ($plan && $plan->is_one_time) {
                    return response()->json([
                        'valid'        => false,
                        'message'      => 'لایسنس این طرح (یک‌بار مصرف) منقضی شده است. این طرح قابل تمدید نیست. لطفاً طرح دیگری خریداری کنید.',
                        'is_one_time'  => true,
                        'expires_at'   => $result['expires_at'] ?? null,
                    ]);
                }
                return response()->json($result);
            }

            $latestVersion = $package->latestVersion()->first();

            if (!$latestVersion) {
                return response()->json(['error' => 'نسخه فعالی برای این پکیج وجود ندارد.'], 422);
            }
            return response()->json([
                'valid'         => true,
                'expires_at'    => $result['expires_at'],
                'days_remaining'=> $result['days_remaining'],
                'is_unlimited'  => $result['is_unlimited'] ?? false,
                'version'       => $latestVersion?->version,
                'signature'     => $latestVersion?->file_hash,
                'download_token' => $latestVersion
                    ? $this->createDownloadToken($license, $latestVersion, $customer)
                    : null,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /* ===================================================================
     *  GET /api/v1/packages/{slug}/check-update
     *  Query: { current_version }
     *  Response: { has_update, latest_version, changelog, what_added, is_mandatory }
     * =================================================================== */
    public function checkUpdate(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'current_version' => 'required|string',
        ]);

        try {
            $customer = $this->authService->authenticate($request);

            $package = Package::where('slug', $slug)
                ->where('status', Package::STATUS_ACTIVE)
                ->first();

            if (!$package) {
                return response()->json(['error' => 'پکیج یافت نشد.'], 404);
            }
            $latestVersion = $package->latestVersion()->first();

            if (!$latestVersion) {
                return response()->json([
                    'has_update' => false,
                    'message'    => 'نسخه فعالی موجود نیست.',
                ]);
            }

            $hasUpdate = version_compare($latestVersion->version, $request->current_version, '>');

            return response()->json([
                'has_update'      => $hasUpdate,
                'latest_version'  => $latestVersion->version,
                'current_version' => $request->current_version,
                'is_mandatory'    => $hasUpdate && $latestVersion->is_mandatory,
                'changelog'       => $latestVersion->changelog,
                'what_added'      => $latestVersion->what_added,
                'what_changed'    => $latestVersion->what_changed,
                'what_fixed'      => $latestVersion->what_fixed,
                'min_php_version'    => $latestVersion->min_php_version,
                'min_laravel_version'=> $latestVersion->min_laravel_version,
                'dependencies'       => $latestVersion->dependencies,
                'file_size'          => $latestVersion->file_size,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    /* ===================================================================
     *  Helper - ساخت download token موقت (15 دقیقه)
     * =================================================================== */
    private function createDownloadToken(
        \App\Models\PackageLicense $license,
        \App\Models\PackageVersion $version,
        Customer $customer
    ): string {
        $token = \App\Models\PackageDownloadToken::create([
            'token'       => \App\Models\PackageDownloadToken::generate(),
            'license_id'  => $license->id,
            'version_id'  => $version->id,
            'customer_id' => $customer->id,
            'expires_at'  => now()->addMinutes(15),
        ]);

        return $token->token;
    }
}
