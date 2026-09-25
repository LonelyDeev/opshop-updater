<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\WithToasts;
use App\Models\Customer;
use App\Models\Gateway;
use App\Models\Package;
use App\Models\PackagePricingPlan;
use App\Models\PackagePurchase;
use App\Services\LicenseService;
use App\Services\PaymentService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('جزئیات پکیج')]
class PackageShow extends Component
{
    use WithToasts;

    public Package $package;

    /** کد آپدیت مشتری (شناسه خرید) */
    public string $updateCode = '';

    /** طرح انتخاب‌شده */
    public string $planId = '';

    /** درگاه پرداخت انتخاب‌شده */
    public string $gatewayKey = '';

    public function mount(string $slug): void
    {
        $package = Package::query()
            ->with(['activePricingPlans', 'images', 'project:id,name'])
            ->where('slug', $slug)
            ->where('status', Package::STATUS_ACTIVE)
            ->first();

        if (!$package) {
            abort(404);
        }

        $this->package = $package;

        // پیش‌انتخاب اولین طرح و اولین درگاه فعال
        $this->planId = (string) ($this->plans->first()?->id ?? '');
        $this->gatewayKey = (string) ($this->gateways->first()?->key ?? '');

        // کد آپدیت معتبر قبلی (session) برای راحتی مشتری
        $this->updateCode = (string) session('shop_update_code', '');
    }

    /* ---------------------------------------------------------------- */
    /*  Computed                                                         */
    /* ---------------------------------------------------------------- */

    #[Computed]
    public function plans()
    {
        return $this->package->activePricingPlans()->get();
    }

    #[Computed]
    public function gateways()
    {
        return Gateway::query()
            ->active()
            ->orderBy('ordering')
            ->orderBy('name')
            ->get();
    }

    /** آخرین ۵ نسخه فعال */
    #[Computed]
    public function versions()
    {
        return $this->package->activeVersions()->limit(5)->get();
    }

    #[Computed]
    public function images()
    {
        return $this->package->images->where('is_active', true);
    }

    /** آیا این پکیج مسیر رایگان دارد؟ */
    #[Computed]
    public function isFreeRoute(): bool
    {
        if ($this->package->is_free) {
            return true;
        }

        return $this->planId !== ''
            && ($plan = $this->plans->firstWhere('id', (int) $this->planId))
            && $plan->final_price <= 0;
    }

    /* ---------------------------------------------------------------- */
    /*  Buy                                                              */
    /* ---------------------------------------------------------------- */

    public function buy(PaymentService $paymentService, LicenseService $licenseService): void
    {
        $rules = [
            'updateCode' => ['required', 'string', 'max:64'],
        ];

        // پکیج غیر رایگان که طرح دارد → انتخاب طرح الزامی است
        if (!$this->package->is_free && $this->plans->isNotEmpty()) {
            $rules['planId'] = ['required'];
        }

        $this->validate($rules, [
            'updateCode.required' => 'کد آپدیت الزامی است.',
            'planId.required'     => 'لطفاً طرح قیمت‌گذاری را انتخاب کنید.',
        ]);

        // ---- مشتری ----
        $customer = Customer::query()
            ->where('update_code', trim($this->updateCode))
            ->first();

        if (!$customer) {
            $this->toast('کد آپدیت نامعتبر است.', 'error');
            return;
        }

        if ($customer->status !== 'active') {
            $this->toast('حساب این مشتری غیرفعال است.', 'error');
            return;
        }

        // ---- طرح ----
        /** @var \App\Models\PackagePricingPlan|null $plan */
        $plan = null;
        if ($this->planId !== '') {
            $plan = $this->package->activePricingPlans()->where('id', (int) $this->planId)->first();

            if (!$plan) {
                $this->toast('طرح قیمت‌گذاری انتخاب‌شده معتبر نیست.', 'error');
                return;
            }
        }

        if (!$this->package->is_free && $this->plans->isNotEmpty() && !$plan) {
            $this->toast('لطفاً طرح قیمت‌گذاری را انتخاب کنید.', 'error');
            return;
        }

        // جلوگیری از خرید مجدد طرح یک‌بار مصرف (مثل جریان API)
        if ($plan && $plan->is_one_time && $plan->hasCustomerUsed($customer->id)) {
            $this->toast('شما قبلاً از این طرح استفاده کرده‌اید. این طرح فقط یک‌بار قابل خریداری است. لطفاً طرح دیگری انتخاب کنید.', 'error');
            return;
        }

        // ---- نسخه ----
        $latestVersion = $this->package->latestVersion()->first();

        if (!$latestVersion) {
            $this->toast('نسخه فعالی برای این پکیج وجود ندارد.', 'error');
            return;
        }

        // ---- مسیر رایگان ----
        if ($this->package->is_free || ($plan && $plan->final_price <= 0)) {
            // پکیج رایگان بدون طرح → لایسنس نامحدود
            if (!$plan) {
                $plan = new PackagePricingPlan([
                    'duration_months' => 0,
                    'is_one_time'     => false,
                ]);
            }

            $purchase = PackagePurchase::create([
                'package_id'      => $this->package->id,
                'version_id'      => $latestVersion->id,
                'pricing_plan_id' => $plan->exists ? $plan->id : null,
                'customer_id'     => $customer->id,
                'amount'          => 0,
                'status'          => PackagePurchase::STATUS_PAID,
                'paid_at'         => now(),
            ]);

            $licenseService->issueOrRenew($purchase, $plan);

            // به‌جای flash از put استفاده می‌کنیم تا پس از رفت‌وبرگشت از درگاه هم
            // کد آپدیت در صفحه پکیج باقی بماند (flash با اولین request بعدی مصرف می‌شود).
            session()->put('shop_update_code', trim($this->updateCode));

            $this->toast('پکیج رایگان با موفقیت دریافت شد؛ لایسنس صادر شد.');
            $this->redirect(route('payment.result', $purchase), navigate: true);

            return;
        }

        // ---- مسیر پرداخت ----
        if ($this->gatewayKey === '' || !$this->gateways->firstWhere('key', $this->gatewayKey)) {
            $this->toast('درگاه پرداخت فعالی تنظیم نشده است.', 'error');
            return;
        }

        $purchase = PackagePurchase::create([
            'package_id'      => $this->package->id,
            'version_id'      => $latestVersion->id,
            'pricing_plan_id' => $plan->id,
            'customer_id'     => $customer->id,
            'callback_url'    => route('payment.callback'),
            'amount'          => $plan->final_price,
            'gateway'         => $this->gatewayKey,
            'status'          => PackagePurchase::STATUS_PENDING,
        ]);

        session()->put('shop_update_code', trim($this->updateCode));

        try {
            $payment = $paymentService->createPayment($purchase, $this->gatewayKey);
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');
            return;
        }

        $this->redirect($payment['payment_url'], navigate: false);
    }

    public function render()
    {
        return view('livewire.shop.package-show');
    }
}
