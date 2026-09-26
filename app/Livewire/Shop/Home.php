<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\WithToasts;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shop')]
#[Title('فروشگاه پکیج‌ها')]
class Home extends Component
{
    use WithPagination, WithToasts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    /** کد آپدیت مشتری برای بررسی اشتراک فعال (از session پیش‌پر می‌شود) */
    public string $updateCode = '';

    /** برچسب دسته‌بندی‌ها (یکسان با پنل مدیریت) */
    public const CATEGORIES = [
        'shop'         => 'فروشگاه',
        'payment'      => 'پرداخت',
        'notification' => 'اعلان',
        'seo'          => 'سئو',
        'blog'         => 'بلاگ',
        'utility'      => 'ابزار',
        'theme'        => 'قالب',
        'other'        => 'سایر',
    ];

    public function mount(): void
    {
        // کد آپدیت ذخیره‌شده (بررسی اشتراک قبلی) برای راحتی مشتری
        $this->updateCode = (string) session('shop_update_code', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    /* ---------------------------------------------------------------- */
    /*  اشتراک فعال مشتری (نشان پکیج‌های رایگان طرح)                     */
    /* ---------------------------------------------------------------- */

    /**
     * بررسی کد آپدیت: اگر مشتری اشتراک فعالِ طرح‌محور داشت، کد در session
     * ذخیره می‌شود تا پکیج‌های پوشش‌داده‌شدهٔ طرح در فروشگاه «رایگان» دیده شوند.
     */
    public function checkMySubscription(): void
    {
        $code = trim($this->updateCode);

        if ($code === '') {
            $this->toast('کد آپدیت الزامی است.', 'error');
            return;
        }

        $customer = Customer::query()->where('update_code', $code)->first();

        if (!$customer) {
            $this->toast('کد آپدیت نامعتبر است.', 'error');
            return;
        }

        if ($customer->status !== 'active') {
            $this->toast('حساب این مشتری غیرفعال است.', 'error');
            return;
        }

        $subscription = $this->findActivePlanSubscription($customer);

        if (!$subscription?->plan) {
            $this->toast('هیچ اشتراک فعالی برای این کد یافت نشد.', 'error');
            return;
        }

        session()->put('shop_update_code', $code);

        // کش computedها را برای رندر همین request بازنشانی می‌کنیم
        unset($this->myActiveSubscription, $this->coveredPackageIds);

        $this->toast('اشتراک فعال شما پیدا شد؛ پکیج‌های طرح «' . $subscription->plan->name . '» برای شما رایگان‌اند.');
    }

    /** حذف کد آپدیت از session و خاموش‌کردن نشان پکیج‌های رایگان */
    public function forgetMySubscription(): void
    {
        session()->forget('shop_update_code');
        $this->reset('updateCode');

        unset($this->myActiveSubscription, $this->coveredPackageIds);

        $this->toast('کد آپدیت شما فراموش شد؛ نشان پکیج‌های رایگان اشتراک برداشته شد.');
    }

    /** اشتراک فعالِ طرح‌محور مشتریِ session (با plan و packages) یا null */
    #[Computed]
    public function myActiveSubscription()
    {
        $code = trim((string) session('shop_update_code', ''));

        if ($code === '') {
            return null;
        }

        $customer = Customer::query()
            ->where('update_code', $code)
            ->where('status', 'active')
            ->first();

        return $customer ? $this->findActivePlanSubscription($customer) : null;
    }

    /** شناسه پکیج‌هایی که طرحِ اشتراک فعالِ session پوشش می‌دهد */
    #[Computed]
    public function coveredPackageIds(): array
    {
        $plan = $this->myActiveSubscription?->plan;

        if (!$plan) {
            return [];
        }

        return $plan->packages->pluck('id')->values()->all();
    }

    /** اولین اشتراک فعالِ طرح‌محور مشتری (با plan.packages eager) */
    protected function findActivePlanSubscription(Customer $customer): ?Subscription
    {
        return $customer->subscriptions()
            ->whereNotNull('subscription_plan_id')
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('plan.packages')
            ->orderByDesc('id')
            ->first();
    }

    #[Computed]
    public function records()
    {
        return Package::query()
            ->with(['latestVersion', 'activePricingPlans'])
            ->where('status', Package::STATUS_ACTIVE)
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->latest()
            ->paginate(12);
    }

    /** دسته‌بندی‌های موجود بین پکیج‌های فعال */
    #[Computed]
    public function categories(): array
    {
        return Package::query()
            ->where('status', Package::STATUS_ACTIVE)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->mapWithKeys(fn ($cat) => [$cat => self::CATEGORIES[$cat] ?? $cat])
            ->all();
    }

    /** آمار کلی فروشگاه */
    #[Computed]
    public function stats(): array
    {
        return [
            'packages'    => Package::where('status', Package::STATUS_ACTIVE)->count(),
            'versions'    => \App\Models\PackageVersion::where('status', \App\Models\PackageVersion::STATUS_ACTIVE)->count(),
            'plans'       => \App\Models\SubscriptionPlan::where('is_active', true)->count(),
            'customers'   => \App\Models\PackagePurchase::distinct('customer_id')->count('customer_id'),
        ];
    }

    /** ۳ طرح اشتراک برتر برای تیزر صفحه اصلی */
    #[Computed]
    public function featuredPlans()
    {
        return \App\Models\SubscriptionPlan::query()
            ->active()
            ->with(['packages:id,name,slug'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(3)
            ->get();
    }

    public function render()
    {
        return view('livewire.shop.home');
    }
}
