<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithToasts;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageLicense;
use App\Models\PackagePurchase;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPlan;
use App\Models\Update;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

#[\Livewire\Attributes\Layout('components.layouts.app')]
#[\Livewire\Attributes\Title('داشبورد')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard');
    }

    /* ------------------------------------------------------------------ */
    /*  Stats                                                              */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function stats(): array
    {
        return [
            [
                'label' => 'مشتریان',
                'value' => Customer::count(),
                'icon' => 'users',
                'variant' => 'primary',
                'hint' => Customer::where('status', 'active')->count() . ' فعال',
                'href' => route('admin.customers.index'),
            ],
            [
                'label' => 'پکیج‌ها',
                'value' => Package::count(),
                'icon' => 'package',
                'variant' => 'info',
                'hint' => Package::where('status', Package::STATUS_ACTIVE)->count() . ' منتشر شده',
                'href' => route('admin.packages.index'),
            ],
            [
                'label' => 'لایسنس‌های فعال',
                'value' => PackageLicense::where('status', PackageLicense::STATUS_ACTIVE)
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->count(),
                'icon' => 'key',
                'variant' => 'warning',
                'hint' => PackageLicense::count() . ' کل',
                'href' => route('admin.licenses.index'),
            ],
            [
                'label' => 'آپدیت‌ها',
                'value' => Update::count(),
                'icon' => 'git-branch',
                'variant' => 'violet',
                'hint' => fa_num(Update::where('status', Update::STATUS_ACTIVE)->count()) . ' فعال',
                'href' => route('admin.updates.index'),
            ],
            [
                'label' => 'اشتراک‌های فعال',
                'value' => Subscription::where('status', 'active')
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->count(),
                'icon' => 'ticket',
                'variant' => 'neutral',
                'hint' => Subscription::count() . ' کل',
                'href' => route('admin.subscriptions.index'),
            ],
            [
                'label' => 'درخواست‌های اشتراک',
                'value' => SubscriptionOrder::where('admin_status', SubscriptionOrder::ADMIN_STATUS_PENDING)
                    ->where('status', SubscriptionOrder::STATUS_PAID)
                    ->count(),
                'icon' => 'hourglass',
                'variant' => 'warning',
                'hint' => fa_num(SubscriptionPlan::where('is_active', true)->count()) . ' طرح فعال',
                'href' => route('admin.subscriptions.orders'),
            ],
            [
                'label' => 'کاربران پنل',
                'value' => User::count(),
                'icon' => 'shield-check',
                'variant' => 'danger',
                'hint' => User::where('status', 'active')->count() . ' فعال',
                'href' => route('admin.users.index'),
            ],
        ];
    }

    #[Computed]
    public function revenue(): array
    {
        $subscriptionsRevenue = $this->schemaHas('subscriptions', 'price') ? (int) Subscription::sum('price') : 0;
        $packageRevenue = (int) PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->sum('amount');
        $downloads = (int) Package::sum('downloads_count');

        return [
            'total' => $subscriptionsRevenue + $packageRevenue,
            'subscriptions' => $subscriptionsRevenue,
            'packages' => $packageRevenue,
            'downloads' => $downloads,
            'paid_purchases' => PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->count(),
            'pending_purchases' => PackagePurchase::where('status', PackagePurchase::STATUS_PENDING)->count(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Charts                                                             */
    /* ------------------------------------------------------------------ */

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

        return ['title' => 'مشتریان جدید (۶ ماه اخیر)', 'series' => $data->all(), 'color' => 'brand'];
    }

    #[Computed]
    public function purchaseChart(): array
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

        return ['title' => 'خرید پکیج‌ها (۶ ماه اخیر)', 'series' => $data->all(), 'color' => 'teal'];
    }

    /* ------------------------------------------------------------------ */
    /*  Recent lists                                                       */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function recentCustomers()
    {
        return Customer::query()
            ->select('id', 'name', 'email', 'phone', 'status', 'created_at')
            ->orderByDesc('created_at')->limit(6)->get();
    }

    #[Computed]
    public function recentUpdates()
    {
        return Update::query()
            ->with('project:id,name')
            ->where('status', Update::STATUS_ACTIVE)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function recentPurchases()
    {
        return PackagePurchase::query()
            ->with(['package:id,name,slug', 'customer:id,name'])
            ->where('status', PackagePurchase::STATUS_PAID)
            ->orderByDesc('paid_at')->limit(6)->get();
    }

    #[Computed]
    public function topPackages()
    {
        return Package::query()
            ->select('id', 'name', 'slug', 'downloads_count', 'purchases_count', 'is_free', 'status')
            ->orderByDesc('purchases_count')->limit(5)->get();
    }

    #[Computed]
    public function expiringLicenses()
    {
        return PackageLicense::query()
            ->with(['package:id,name', 'customer:id,name'])
            ->where('status', PackageLicense::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(14)])
            ->orderBy('expires_at')->limit(5)->get();
    }

    #[Computed]
    public function updatesBreakdown(): array
    {
        return [
            'active' => Update::where('status', Update::STATUS_ACTIVE)->count(),
            'draft' => Update::where('status', Update::STATUS_DRAFT)->count(),
            'archived' => Update::where('status', Update::STATUS_ARCHIVED)->count(),
        ];
    }

    private function schemaHas(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
