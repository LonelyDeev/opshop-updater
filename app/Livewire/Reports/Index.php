<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithToasts;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPlan;
use App\Models\Update;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('گزارش‌ها')]
class Index extends Component
{
    use WithPagination, WithToasts;

    /** تب مشتریان: بازه تاریخ عضویت */
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    /** تب آپدیت‌ها: فیلتر پروژه و وضعیت */
    #[Url]
    public string $project = '';

    #[Url]
    public string $status = '';

    public function updatedFrom(): void
    {
        $this->resetPage('customersPage');
    }

    public function updatedTo(): void
    {
        $this->resetPage('customersPage');
    }

    public function updatedProject(): void
    {
        $this->resetPage('updatesPage');
    }

    public function updatedStatus(): void
    {
        $this->resetPage('updatesPage');
    }

    public function resetFilters(): void
    {
        $this->reset(['from', 'to', 'project', 'status']);
        $this->resetPage('customersPage');
        $this->resetPage('updatesPage');
    }

    /* ------------------------------------------------------------------ */
    /*  تب «نمای کلی»                                                      */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function overviewStats(): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();

        $totalUpdates = Update::count();
        $activeUpdates = Update::where('status', Update::STATUS_ACTIVE)->count();

        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        $subscriptionsRevenue = (int) Subscription::where('payment_status', 'paid')->sum('final_amount');
        $packageRevenue = (int) PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->sum('amount');

        $planStats = $this->plansStats;

        return [
            [
                'label' => 'کل مشتریان',
                'value' => $totalCustomers,
                'icon' => 'users',
                'variant' => 'primary',
                'hint' => fa_num($activeCustomers) . ' فعال',
                'href' => route('admin.customers.index'),
            ],
            [
                'label' => 'آپدیت‌های فعال',
                'value' => $activeUpdates,
                'icon' => 'git-branch',
                'variant' => 'info',
                'hint' => 'از ' . fa_num($totalUpdates) . ' آپدیت',
                'href' => route('admin.updates.index'),
            ],
            [
                'label' => 'اشتراک‌های فعال',
                'value' => $activeSubscriptions,
                'icon' => 'ticket',
                'variant' => 'warning',
                'hint' => 'از ' . fa_num($totalSubscriptions) . ' اشتراک',
                'href' => route('admin.subscriptions.index'),
            ],
            [
                'label' => 'کاربران پنل',
                'value' => User::count(),
                'icon' => 'shield-check',
                'variant' => 'violet',
                'hint' => 'مدیران و اپراتورها',
                'href' => route('admin.users.index'),
            ],
            [
                'label' => 'خریدهای موفق پکیج',
                'value' => PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->count(),
                'icon' => 'package',
                'variant' => 'neutral',
                'hint' => PackagePurchase::where('status', PackagePurchase::STATUS_PENDING)->count() . ' در انتظار پرداخت',
                'href' => route('admin.purchases.index'),
            ],
            [
                'label' => 'سفارش‌های اشتراک',
                'value' => $planStats['total_orders'],
                'icon' => 'crown',
                'variant' => 'warning',
                'hint' => fa_num($planStats['pending_orders']) . ' در انتظار تأیید',
                'href' => route('admin.subscriptions.orders'),
            ],
            [
                'label' => 'درآمد طرح‌ها',
                'value' => money($planStats['plan_revenue'], false),
                'icon' => 'wallet',
                'variant' => 'primary',
                'hint' => 'سفارش‌های پرداخت‌شده طرح‌ها',
                'href' => route('admin.plans.index'),
            ],
            [
                'label' => 'درآمد کل',
                'value' => money($subscriptionsRevenue + $packageRevenue, false),
                'icon' => 'wallet',
                'variant' => 'primary',
                'hint' => 'اشتراک‌ها + پکیج‌ها (تومان)',
                'href' => route('admin.reports.index'),
            ],
        ];
    }

    #[Computed]
    public function customerChart(): array
    {
        $data = collect(range(5, 0))->map(function ($i) {
            $date = Carbon::now()->subMonths($i);

            return [
                'label' => verta_date($date, 'Y/m'),
                'value' => Customer::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
            ];
        });

        return ['title' => 'روند ثبت‌نام مشتریان (۶ ماه اخیر)', 'series' => $data->all(), 'color' => 'brand'];
    }

    #[Computed]
    public function subscriptionBreakdown(): array
    {
        $total = Subscription::count();
        $active = Subscription::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();
        $expired = Subscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'inactive' => max(0, $total - $active - $expired),
        ];
    }

    #[Computed]
    public function summaryMetrics(): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();

        $updates = [
            'total' => Update::count(),
            'active' => Update::where('status', Update::STATUS_ACTIVE)->count(),
            'draft' => Update::where('status', Update::STATUS_DRAFT)->count(),
            'archived' => Update::where('status', Update::STATUS_ARCHIVED)->count(),
        ];

        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->count();

        $totalPurchases = PackagePurchase::count();
        $paidPurchases = PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->count();
        $pendingPurchases = PackagePurchase::where('status', PackagePurchase::STATUS_PENDING)->count();
        $failedPurchases = PackagePurchase::where('status', PackagePurchase::STATUS_FAILED)->count();

        $subscriptionsRevenue = (int) Subscription::where('payment_status', 'paid')->sum('final_amount');
        $packageRevenue = (int) PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->sum('amount');

        $planStats = $this->plansStats;

        return [
            [
                'icon' => 'users',
                'title' => 'مشتریان',
                'rows' => [
                    ['label' => 'کل مشتریان', 'value' => fa_num($totalCustomers)],
                    ['label' => 'فعال', 'value' => fa_num($activeCustomers)],
                    ['label' => 'غیرفعال', 'value' => fa_num($totalCustomers - $activeCustomers)],
                ],
            ],
            [
                'icon' => 'git-branch',
                'title' => 'آپدیت‌ها',
                'rows' => [
                    ['label' => 'کل آپدیت‌ها', 'value' => fa_num($updates['total'])],
                    ['label' => 'فعال', 'value' => fa_num($updates['active'])],
                    ['label' => 'پیش‌نویس', 'value' => fa_num($updates['draft'])],
                    ['label' => 'بایگانی شده', 'value' => fa_num($updates['archived'])],
                ],
            ],
            [
                'icon' => 'ticket',
                'title' => 'اشتراک‌ها',
                'rows' => [
                    ['label' => 'کل اشتراک‌ها', 'value' => fa_num($totalSubscriptions)],
                    ['label' => 'فعال', 'value' => fa_num($activeSubscriptions)],
                    ['label' => 'منقضی شده', 'value' => fa_num($expiredSubscriptions)],
                    ['label' => 'غیرفعال / معلق', 'value' => fa_num(max(0, $totalSubscriptions - $activeSubscriptions - $expiredSubscriptions))],
                ],
            ],
            [
                'icon' => 'package',
                'title' => 'فروش پکیج‌ها',
                'rows' => [
                    ['label' => 'کل خریدها', 'value' => fa_num($totalPurchases)],
                    ['label' => 'پرداخت‌شده', 'value' => fa_num($paidPurchases)],
                    ['label' => 'در انتظار پرداخت', 'value' => fa_num($pendingPurchases)],
                    ['label' => 'ناموفق', 'value' => fa_num($failedPurchases)],
                ],
            ],
            [
                'icon' => 'crown',
                'title' => 'طرح‌های اشتراک',
                'rows' => [
                    ['label' => 'طرح فعال', 'value' => fa_num($planStats['active_plans']) . ' از ' . fa_num(SubscriptionPlan::count()) . ' طرح'],
                    ['label' => 'سفارش تأییدشده', 'value' => fa_num($planStats['approved_orders'])],
                    ['label' => 'در انتظار تأیید', 'value' => fa_num($planStats['pending_orders'])],
                    ['label' => 'ردشده', 'value' => fa_num($planStats['rejected_orders'])],
                    ['label' => 'درآمد طرح‌ها', 'value' => money($planStats['plan_revenue'])],
                ],
            ],
            [
                'icon' => 'wallet',
                'title' => 'مالی',
                'rows' => [
                    ['label' => 'درآمد اشتراک‌ها (پرداخت‌شده)', 'value' => money($subscriptionsRevenue)],
                    ['label' => 'درآمد پکیج‌ها (پرداخت‌شده)', 'value' => money($packageRevenue)],
                    ['label' => 'درآمد کل', 'value' => money($subscriptionsRevenue + $packageRevenue)],
                    ['label' => 'مجموع دانلود پکیج‌ها', 'value' => fa_num((int) Package::sum('downloads_count'))],
                ],
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  تب «مشتریان»                                                       */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function customersReport()
    {
        return Customer::query()
            ->withCount('subscriptions')
            ->addSelect([
                'subscriptions_spent' => Subscription::selectRaw('coalesce(sum(final_amount), 0)')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('payment_status', 'paid'),
                'purchases_spent' => PackagePurchase::selectRaw('coalesce(sum(amount), 0)')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('status', PackagePurchase::STATUS_PAID),
            ])
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->orderByDesc('created_at')
            ->paginate(12, ['*'], 'customersPage');
    }

    /* ------------------------------------------------------------------ */
    /*  تب «آپدیت‌ها»                                                      */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function projects()
    {
        return Project::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function updatesReport()
    {
        return Update::query()
            ->with('project:id,name')
            ->when($this->project, fn ($q) => $q->where('project_id', (int) $this->project))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(12, ['*'], 'updatesPage');
    }

    /* ------------------------------------------------------------------ */
    /*  تب «فروش»                                                          */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function revenue(): array
    {
        $subscriptionsRevenue = (int) Subscription::where('payment_status', 'paid')->sum('final_amount');
        $packageRevenue = (int) PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->sum('amount');

        return [
            'total' => $subscriptionsRevenue + $packageRevenue,
            'subscriptions' => $subscriptionsRevenue,
            'packages' => $packageRevenue,
            'paid_purchases' => PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->count(),
            'pending_purchases' => PackagePurchase::where('status', PackagePurchase::STATUS_PENDING)->count(),
            'failed_purchases' => PackagePurchase::where('status', PackagePurchase::STATUS_FAILED)->count(),
        ];
    }

    #[Computed]
    public function salesChart(): array
    {
        $data = collect(range(5, 0))->map(function ($i) {
            $date = Carbon::now()->subMonths($i);
            $month = PackagePurchase::where('status', PackagePurchase::STATUS_PAID)
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month);

            return [
                'label' => verta_date($date, 'Y/m'),
                'value' => $month->count(),
                'secondary' => (int) $month->sum('amount'),
            ];
        });

        return ['title' => 'خریدهای پرداخت‌شده (۶ ماه اخیر)', 'series' => $data->all(), 'color' => 'teal'];
    }

    #[Computed]
    public function topPackages()
    {
        return Package::query()
            ->select('id', 'name', 'slug', 'is_free', 'default_price', 'downloads_count', 'purchases_count', 'status')
            ->withCount(['purchases as paid_purchases_count' => fn ($q) => $q->where('status', PackagePurchase::STATUS_PAID)])
            ->withSum(['purchases as paid_purchases_revenue' => fn ($q) => $q->where('status', PackagePurchase::STATUS_PAID)], 'amount')
            ->orderByDesc('paid_purchases_count')
            ->orderByDesc('purchases_count')
            ->limit(5)
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /*  تب «طرح‌های اشتراک»                                                */
    /* ------------------------------------------------------------------ */

    /** خلاصه آمار طرح‌های اشتراک (همان planStats داشبورد) */
    #[Computed]
    public function plansStats(): array
    {
        $pendingApprovals = SubscriptionOrder::where('admin_status', SubscriptionOrder::ADMIN_STATUS_PENDING)
            ->where('status', SubscriptionOrder::STATUS_PAID)
            ->count();

        return [
            'active_plans' => SubscriptionPlan::where('is_active', true)->count(),
            'total_orders' => SubscriptionOrder::count(),
            'approved_orders' => SubscriptionOrder::where('admin_status', SubscriptionOrder::ADMIN_STATUS_APPROVED)->count(),
            'pending_orders' => $pendingApprovals,
            'rejected_orders' => SubscriptionOrder::where('admin_status', SubscriptionOrder::ADMIN_STATUS_REJECTED)->count(),
            'plan_revenue' => (int) SubscriptionOrder::where('status', SubscriptionOrder::STATUS_PAID)->sum('final_amount'),
            'active_plan_subscriptions' => Subscription::whereNotNull('subscription_plan_id')
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count(),
            'pending_approvals' => $pendingApprovals,
        ];
    }

    /** گزارش کامل همه طرح‌ها، مرتب بر اساس درآمد */
    #[Computed]
    public function plansReport()
    {
        return SubscriptionPlan::query()
            ->with('packages:id,name')
            ->withCount('orders')
            ->withCount(['orders as approved_orders_count' => fn ($q) => $q->where('admin_status', SubscriptionOrder::ADMIN_STATUS_APPROVED)])
            ->withCount(['orders as pending_orders_count' => fn ($q) => $q->where('admin_status', SubscriptionOrder::ADMIN_STATUS_PENDING)
                ->where('status', SubscriptionOrder::STATUS_PAID)])
            ->withCount(['orders as rejected_orders_count' => fn ($q) => $q->where('admin_status', SubscriptionOrder::ADMIN_STATUS_REJECTED)])
            ->withSum(['orders as revenue' => fn ($q) => $q->where('status', SubscriptionOrder::STATUS_PAID)], 'final_amount')
            ->withCount('packages')
            ->addSelect([
                'active_subs_count' => Subscription::selectRaw('count(*)')
                    ->whereColumn('subscription_plan_id', 'subscription_plans.id')
                    ->where('status', 'active')
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            ])
            ->orderByDesc('revenue')
            ->orderByDesc('orders_count')
            ->orderBy('sort_order')
            ->get();
    }

    public function render()
    {
        return view('livewire.reports.index');
    }
}
