<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Package;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use App\Services\PackageApiAuthService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * API طرح‌های اشتراک (برای پروژه فروشگاه کاربر):
 *  - GET  /api/v1/plans                        → فهرست طرح‌های فعال + پکیج‌های هر طرح + is_requested
 *  - POST /api/v1/plans/{id}/purchase          → ثبت درخواست خرید طرح (پرداخت درگاه / طرح رایگان)
 *  - GET  /api/v1/subscription-requests/{id}   → وضعیت درخواست (فعال‌سازی پس از تأیید مدیر)
 *
 * نکته: فعال‌سازی طرح (صدور لایسنس‌ها) بعد از تأیید مدیر انجام می‌شود؛ فروشگاه بیرونی
 * باید پس از بازگشت از درگاه، وضعیت درخواست را poll کند تا لایسنس‌ها را دریافت کند.
 */
class ApiSubscriptionPlanController extends Controller
{
    public function __construct(
        private PackageApiAuthService $authService,
        private SubscriptionService $subscriptionService
    ) {}

    /* ===================================================================
     *  GET /api/v1/plans
     *  فهرست طرح‌های فعال (که حداقل یک پکیج فعال دارند)، مرتب بر اساس sort_order.
     *  برای هر طرح: قیمت‌ها، قابلیت‌ها، پکیج‌های فعال + مدت دسترسی هر پکیج
     *  و پرچم is_requested (درخواست ثبت‌شده/تأییدشده برای همین مشتری)
     *  تا فروشگاه به‌جای «خرید»، نشان «درخواست ثبت شده» دهد.
     * =================================================================== */
    public function index(Request $request): JsonResponse
    {
        try {
            $customer = $this->authService->authenticate($request);

            $plans = SubscriptionPlan::query()
                ->where('is_active', true)
                ->whereHas('packages', fn ($q) => $q->where('status', Package::STATUS_ACTIVE))
                ->with([
                    'packages' => fn ($q) => $q
                        ->where('status', Package::STATUS_ACTIVE)
                        ->select('packages.id', 'packages.name', 'packages.slug', 'packages.thumbnail'),
                ])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            // طرح‌هایی که این مشتری برایشان درخواست ثبت‌شده (pending+پرداخت‌شده/رایگان)
            // یا تأییدشده دارد — یک کوئری برای همه طرح‌ها
            $requestedPlanIds = SubscriptionRequest::query()
                ->where('customer_id', $customer->id)
                ->where(function ($q) {
                    $q->where('status', SubscriptionRequest::STATUS_APPROVED)
                        ->orWhere(function ($sq) {
                            $sq->where('status', SubscriptionRequest::STATUS_PENDING)
                                ->whereIn('payment_status', [
                                    SubscriptionRequest::PAYMENT_PAID,
                                    SubscriptionRequest::PAYMENT_FREE,
                                ]);
                        });
                })
                ->pluck('subscription_plan_id')
                ->unique()
                ->values()
                ->all();

            return response()->json([
                'data' => $plans->map(function (SubscriptionPlan $plan) use ($requestedPlanIds) {
                    return [
                        'id'               => $plan->id,
                        'name'             => $plan->name,
                        'description'      => $plan->description,
                        'duration_months'  => $plan->duration_months,
                        'duration_label'   => $plan->duration_label,
                        'price'            => $plan->price,
                        'discount_price'   => $plan->discount_price,
                        'final_price'      => $plan->final_price,
                        'has_discount'     => $plan->has_discount,
                        'discount_percent' => $plan->discount_percent,
                        'is_free'          => $plan->is_free,
                        'is_one_time'      => $plan->is_one_time,
                        'features'         => $plan->features ?? [],
                        'packages_count'   => $plan->packages->count(),
                        'packages'         => $plan->packages->map(function (Package $package) use ($plan) {
                            $duration = $package->pivot->duration_months !== null
                                ? (int) $package->pivot->duration_months
                                : null;

                            return [
                                'id'              => $package->id,
                                'name'            => $package->name,
                                'slug'            => $package->slug,
                                'thumbnail_url'   => $package->thumbnail_url,
                                'duration_months' => $duration,
                                'duration_label'  => $this->packageDurationLabel($duration, $plan),
                            ];
                        })->values()->all(),
                        'is_requested' => in_array($plan->id, $requestedPlanIds, true),
                    ];
                })->values()->all(),
                'meta' => [
                    'total' => $plans->count(),
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    /* ===================================================================
     *  POST /api/v1/plans/{plan}/purchase
     *  ثبت درخواست خرید طرح + دریافت payment_url
     *  Body: { callback_url: required|url, gateway?: nullable|string|max:32 }
     *  - طرح رایگان → مستقیم در انتظار تأیید مدیر (payment_status=free)
     *  - طرح پولی  → payment_url (مسیر امضادار روی پنل / آدرس درگاه)؛
     *    پس از بازگشت از درگاه و تأیید پرداخت، درخواست همچنان «در انتظار تأیید مدیر»
     *    می‌ماند و فعال‌سازی پس از تأیید مدیر انجام می‌شود.
     *  - برای طرح یک‌بارمصرف، خرید مجدد مسدود است (تأییدشده قبلی).
     *  - درخواست در جریان (pending + پرداخت‌شده/رایگان) → خطا تا رفع وضعیت توسط مدیر.
     * =================================================================== */
    public function purchase(Request $request, int $plan): JsonResponse
    {
        $request->validate([
            'callback_url' => 'required|url',
            'gateway'      => 'nullable|string|max:32',
        ]);

        try {
            $customer = $this->authService->authenticate($request);

            // درگاه اختیاری: کلاینت می‌تواند درگاه مشخصی بخواهد (مثلاً local برای تست)
            // باید در فهرست درگاه‌های پشتیبانی‌شده + فعال باشد.
            $gatewayKey = $request->input('gateway');
            if ($gatewayKey) {
                $gatewayActive = Gateway::query()
                    ->where('key', $gatewayKey)
                    ->whereIn('key', array_keys(config('general.supported_gateways')))
                    ->where('is_active', true)
                    ->exists();

                if (!$gatewayActive) {
                    return response()->json([
                        'error' => "درگاه «{$gatewayKey}» وجود ندارد یا فعال نیست. درگاه‌های فعال را از فهرست پکیج‌ها یا پنل بررسی کنید.",
                    ], 422);
                }
            }

            $planModel = SubscriptionPlan::query()
                ->where('id', $plan)
                ->where('is_active', true)
                ->first();

            if (!$planModel) {
                return response()->json(['error' => 'طرح یافت نشد.'], 404);
            }

            if ($planModel->packages()->count() === 0) {
                return response()->json(['error' => 'این طرح هنوز پکیجی ندارد.'], 422);
            }

            // جلوگیری از خرید مجدد طرح‌های یک‌بار مصرف
            if ($planModel->is_one_time && $planModel->hasCustomerUsed($customer->id)) {
                return response()->json([
                    'error' => 'شما قبلاً از این طرح استفاده کرده‌اید. این طرح فقط یک‌بار قابل خریداری است. لطفاً طرح دیگری انتخاب کنید.',
                ], 422);
            }

            // درخواست در جریان برای همین طرح → تا تأیید/رد مدیر، درخواست جدید نباید ثبت شود
            if ($planModel->hasCustomerPending($customer->id)) {
                return response()->json([
                    'error' => 'شما یک درخواست در جریان برای این طرح دارید که در انتظار تأیید مدیر است.',
                ], 422);
            }

            $result = $this->subscriptionService->createRequest(
                $customer,
                $planModel,
                $gatewayKey,
                $request->input('callback_url')
            );

            /** @var SubscriptionRequest $subscriptionRequest */
            $subscriptionRequest = $result['request'];

            // طرح رایگان: بدون پرداخت، مستقیم در انتظار تأیید مدیر
            if ($result['payment_url'] === null) {
                return response()->json([
                    'is_free'        => true,
                    'request_id'     => $subscriptionRequest->id,
                    'status'         => $subscriptionRequest->status,
                    'payment_status' => $subscriptionRequest->payment_status,
                    'message'        => 'درخواست شما ثبت شد و در انتظار تأیید مدیر است.',
                ]);
            }

            // طرح پولی: PaymentService هنگام ایجاد تراکنش، transaction_id/gateway را
            // روی درخواست ذخیره کرده است.
            $subscriptionRequest->refresh();

            return response()->json([
                'payment_url'    => $result['payment_url'],
                'transaction_id' => (string) $subscriptionRequest->transaction_id,
                'amount'         => (int) $subscriptionRequest->amount,
                'gateway'        => $subscriptionRequest->gateway,
                'request_id'     => $subscriptionRequest->id,
                'status'         => $subscriptionRequest->status,
                'payment_status' => $subscriptionRequest->payment_status,
                'message'        => 'پس از پرداخت، درخواست شما در انتظار تأیید مدیر قرار می‌گیرد.',
            ]);
        } catch (RuntimeException $e) {
            // 403 = احراز هویت؛ بقیه RuntimeException ها خطای منطق کسب‌وکار (422)
            return response()->json(['error' => $e->getMessage()], $e->getCode() === 403 ? 403 : 422);
        }
    }

    /* ===================================================================
     *  GET /api/v1/subscription-requests/{request}
     *  وضعیت درخواست خرید طرح (متعلق به همین مشتری):
     *  - pending + پرداخت‌شده/رایگان → «در انتظار تأیید مدیر»
     *  - approved → لایسنس‌های صادرشده (برای دانلود/نمایش فروشگاه)
     *  - rejected → یادداشت مدیر
     *  فروشگاه پس از بازگشت از درگاه این endpoint را poll می‌کند.
     * =================================================================== */
    public function requestStatus(Request $httpRequest, int $request): JsonResponse
    {
        try {
            $customer = $this->authService->authenticate($httpRequest);

            $subscriptionRequest = SubscriptionRequest::query()
                ->with('plan')
                ->find($request);

            if (!$subscriptionRequest) {
                return response()->json(['error' => 'درخواست یافت نشد.'], 404);
            }

            if ($subscriptionRequest->customer_id !== $customer->id) {
                return response()->json(['error' => 'این درخواست متعلق به شما نیست.'], 403);
            }

            return response()->json([
                'data' => $this->serializeRequest($subscriptionRequest),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    /* ===================================================================
     *  Helper - برچسب مدت دسترسی یک پکیج داخل طرح
     *  - مدت اختصاصی pivot (0 → نامحدود، N → «N ماه/سال»)
     *  - null → مدت پیش‌فرض طرح («مدت پیش‌فرض طرح (N ماه)» یا «نامحدود»)
     * =================================================================== */
    private function packageDurationLabel(?int $duration, SubscriptionPlan $plan): string
    {
        if ($duration === 0) {
            return 'نامحدود';
        }

        if ($duration === null) {
            return $plan->duration_months === 0
                ? 'نامحدود'
                : 'مدت پیش‌فرض طرح (' . $plan->duration_label . ')';
        }

        return $this->formatMonths($duration);
    }

    /** فرمت مدت ماه، هم‌ساخت با SubscriptionPlan::getDurationLabelAttribute */
    private function formatMonths(int $months): string
    {
        if ($months === 0) {
            return 'نامحدود';
        }

        if ($months < 12) {
            return $months . ' ماه';
        }

        $years = $months / 12;

        return ($years == floor($years) ? (int) $years : $years) . ' سال';
    }

    /* ===================================================================
     *  Helper - سریال‌سازی درخواست اشتراک برای فروشگاه
     * =================================================================== */
    private function serializeRequest(SubscriptionRequest $req): array
    {
        $plan = $req->plan;

        return [
            'id'             => $req->id,
            'plan'           => $plan ? [
                'id'             => $plan->id,
                'name'           => $plan->name,
                'duration_label' => $plan->duration_label,
                'final_price'    => $plan->final_price,
            ] : null,
            'amount'         => $req->amount,
            'gateway'        => $req->gateway,
            'transaction_id' => $req->transaction_id,
            'payment_status' => $req->payment_status,
            'status'         => $req->status,
            'admin_note'     => $req->admin_note,
            'requested_at'   => $req->created_at?->toDateTimeString(),
            'paid_at'        => $req->paid_at?->toDateTimeString(),
            'approved_at'    => $req->approved_at?->toDateTimeString(),
            'rejected_at'    => $req->rejected_at?->toDateTimeString(),
            'message'        => $this->statusMessage($req),
            // لایسنس‌ها فقط بعد از تأیید مدیر صادر می‌شوند
            'licenses'       => $req->status === SubscriptionRequest::STATUS_APPROVED
                ? $this->licensePayload($req)
                : [],
        ];
    }

    /** پیام وضعیت بر اساس ماشین وضعیت پرداخت/تأیید */
    private function statusMessage(SubscriptionRequest $req): string
    {
        if ($req->status === SubscriptionRequest::STATUS_APPROVED) {
            return 'طرح فعال شد — لایسنس‌ها صادر شدند.';
        }

        if ($req->status === SubscriptionRequest::STATUS_REJECTED) {
            return trim('درخواست رد شد. ' . (string) $req->admin_note);
        }

        return match ($req->payment_status) {
            SubscriptionRequest::PAYMENT_PAID => 'پرداخت تأیید شد؛ درخواست شما در انتظار تأیید مدیر است.',
            SubscriptionRequest::PAYMENT_FREE => 'درخواست شما ثبت شد و در انتظار تأیید مدیر است.',
            SubscriptionRequest::PAYMENT_FAILED => trim(
                (($req->meta['fail_reason'] ?? '') ? $req->meta['fail_reason'] . ' — ' : '')
                . 'پرداخت ناموفق بوده است.'
            ),
            default => 'درخواست در انتظار پرداخت است.',
        };
    }

    /** لایسنس‌های صادرشده: اولوا با رکوردهای زنده، در غیر این صورت meta activated_licenses */
    private function licensePayload(SubscriptionRequest $req): array
    {
        $licenses = $req->licenses()->with('package:id,name')->get();

        if ($licenses->isNotEmpty()) {
            return $licenses->map(fn ($license) => [
                'package_id'     => $license->package_id,
                'package'        => $license->package?->name,
                'license_key'    => $license->license_key,
                'expires_at'     => $license->expires_at?->toDateTimeString(),
                'is_unlimited'   => $license->expires_at === null,
                'days_remaining' => $license->days_remaining,
            ])->values()->all();
        }

        // fallback: لایسنس‌هایی که هنگام تأیید در meta ذخیره شده‌اند
        $activated = (array) ($req->meta['activated_licenses'] ?? []);

        return array_map(function (array $item) {
            $expiresAt = $item['expires_at'] ?? null;

            return [
                'package_id'     => $item['package_id'] ?? null,
                'package'        => $item['package'] ?? null,
                'license_key'    => $item['license_key'] ?? null,
                'expires_at'     => $expiresAt,
                'is_unlimited'   => $expiresAt === null,
                'days_remaining' => $expiresAt !== null
                    ? max(0, (int) now()->diffInDays(Carbon::parse($expiresAt), false))
                    : null,
            ];
        }, $activated);
    }
}
