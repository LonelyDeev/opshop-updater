<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Update;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. آمارهای کلی
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();

        $totalUpdates = Update::count();
        // استفاده از intval برای اطمینان از عدد بودن
        $publishedUpdates = intval(Update::where('status', 'published')->count());

        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = intval(Subscription::where('status', 'active')
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })->count());

        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();

        // 2. داده‌های نمودار مشتریان
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

        // 3. داده‌های نمودار وضعیت آپدیت‌ها (رفع باگ با intval)
        $draftUpdates = intval(Update::where('status', 'draft')->count());
        $publishedStatus = intval(Update::where('status', 'published')->count());
        $archivedUpdates = intval(Update::where('status', 'archived')->count());

        // 4. آخرین مشتریان
        $recentCustomers = Customer::withCount('subscriptions')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 5. آخرین آپدیت‌ها
        $recentUpdates = Update::where('status', 'published')
            ->with('project') // لود کردن رابطه پروژه برای جلوگیری از خطای N+1
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 6. درآمد کل (مدیریت خطای نبودن ستون price)
        $totalRevenue = 0;
        try {
            // بررسی می‌کنیم ستون price وجود داشته باشد
            if (DB::getSchemaBuilder()->hasColumn('subscriptions', 'price')) {
                $totalRevenue = Subscription::sum('price');
            }
        } catch (\Exception $e) {
            $totalRevenue = 0;
        }

        return view('back.dashboard', compact(
            'totalCustomers', 'activeCustomers',
            'totalUpdates', 'publishedUpdates',
            'totalSubscriptions', 'activeSubscriptions',
            'totalUsers', 'activeUsers',
            'customerLabels', 'customerData',
            'draftUpdates', 'publishedStatus', 'archivedUpdates',
            'recentCustomers', 'recentUpdates',
            'totalRevenue'
        ));
    }
}
