<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageLicense;
use App\Models\PackagePurchase;
use App\Models\Subscription;
use App\Models\Update;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ===== 1. آمارهای کلی مشتریان و آپدیت‌ها (موجود) =====
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();

        $totalUpdates = Update::count();
        $publishedUpdates = intval(Update::where('status', 'published')->count());

        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = intval(Subscription::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count());

        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();

        // ===== 2. آمار پکیج‌ها (جدید) =====
        $totalPackages = Package::count();
        $activePackages = Package::where('status', Package::STATUS_ACTIVE)->count();
        $freePackages  = Package::where('is_free', true)->count();
        $paidPackages  = $totalPackages - $freePackages;

        // لایسنس‌ها
        $totalLicenses     = PackageLicense::count();
        $activeLicenses    = PackageLicense::where('status', PackageLicense::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();
        $expiredLicenses   = PackageLicense::where(function ($q) {
            $q->where('status', PackageLicense::STATUS_EXPIRED)
              ->orWhere(function ($sq) {
                  $sq->where('status', PackageLicense::STATUS_ACTIVE)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
              });
        })->count();
        $revokedLicenses   = PackageLicense::where('status', PackageLicense::STATUS_REVOKED)->count();

        // خریدهای پکیج
        $totalPurchases    = PackagePurchase::count();
        $paidPurchases     = PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->count();
        $pendingPurchases  = PackagePurchase::where('status', PackagePurchase::STATUS_PENDING)->count();
        $failedPurchases   = PackagePurchase::where('status', PackagePurchase::STATUS_FAILED)->count();

        // درآمد و دانلود پکیج‌ها
        $packageRevenue    = PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->sum('amount');
        $totalDownloads    = Package::sum('downloads_count');

        // ===== 3. نمودار مشتریان (موجود) =====
        $customerLabels = [];
        $customerData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $customerLabels[] = $date->format('Y/m');
            $count = Customer::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $customerData[] = $count;
        }

        // ===== 4. نمودار خرید پکیج‌ها (جدید) =====
        $packagePurchaseLabels = [];
        $packagePurchaseData = [];
        $packageRevenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $packagePurchaseLabels[] = $date->format('Y/m');

            $monthPurchases = PackagePurchase::where('status', PackagePurchase::STATUS_PAID)
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month);

            $packagePurchaseData[] = $monthPurchases->count();
            $packageRevenueData[]  = $monthPurchases->sum('amount');
        }

        // ===== 5. نمودار وضعیت آپدیت‌ها (موجود) =====
        $draftUpdates = intval(Update::where('status', 'draft')->count());
        $publishedStatus = intval(Update::where('status', 'published')->count());
        $archivedUpdates = intval(Update::where('status', 'archived')->count());

        // ===== 6. آخرین مشتریان (موجود) =====
        $recentCustomers = Customer::withCount('subscriptions')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ===== 7. آخرین آپدیت‌ها (موجود) =====
        $recentUpdates = Update::where('status', 'published')
            ->with('project')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ===== 8. آخرین خریدهای پکیج (جدید) =====
        $recentPackagePurchases = PackagePurchase::with(['package', 'customer', 'license'])
            ->where('status', PackagePurchase::STATUS_PAID)
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get();

        // ===== 9. پکیج‌های پرفروش (جدید) =====
        $topPackages = Package::withCount(['purchases as paid_purchases_count' => function ($q) {
                $q->where('status', PackagePurchase::STATUS_PAID);
            }])
            ->orderByDesc('paid_purchases_count')
            ->limit(5)
            ->get();

        // ===== 10. لایسنس‌های در حال انقضا (14 روز آینده) (جدید) =====
        $expiringLicenses = PackageLicense::with(['package', 'customer'])
            ->where('status', PackageLicense::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(14)])
            ->orderBy('expires_at')
            ->limit(5)
            ->get();

        // ===== 11. درآمد کل (موجود) =====
        $totalRevenue = 0;
        try {
            if (DB::getSchemaBuilder()->hasColumn('subscriptions', 'price')) {
                $totalRevenue = Subscription::sum('price');
            }
        } catch (\Exception $e) {
            $totalRevenue = 0;
        }

        // درآمد کل (اشتراک + پکیج)
        $grandTotalRevenue = $totalRevenue + $packageRevenue;

        return view('back.dashboard', compact(
            // مشتریان
            'totalCustomers', 'activeCustomers',
            // آپدیت‌ها
            'totalUpdates', 'publishedUpdates',
            'draftUpdates', 'publishedStatus', 'archivedUpdates',
            // اشتراک‌ها
            'totalSubscriptions', 'activeSubscriptions',
            // کاربران
            'totalUsers', 'activeUsers',
            // درآمد اشتراک
            'totalRevenue',
            // پکیج‌ها
            'totalPackages', 'activePackages', 'freePackages', 'paidPackages',
            // لایسنس‌ها
            'totalLicenses', 'activeLicenses', 'expiredLicenses', 'revokedLicenses',
            // خریدها
            'totalPurchases', 'paidPurchases', 'pendingPurchases', 'failedPurchases',
            // درآمد و دانلود پکیج
            'packageRevenue', 'totalDownloads', 'grandTotalRevenue',
            // داده‌های نمودار
            'customerLabels', 'customerData',
            'packagePurchaseLabels', 'packagePurchaseData', 'packageRevenueData',
            // جداول اخیر
            'recentCustomers', 'recentUpdates',
            'recentPackagePurchases', 'topPackages', 'expiringLicenses'
        ));
    }
}
