<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Update;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        // آمار کلی
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $totalUpdates = Update::count();
        $publishedUpdates = Update::where('status', 'published')->count();
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')->where('expires_at', '>', now())->count();
        $expiredSubscriptions = Subscription::where('status', 'active')->where('expires_at', '<=', now())->count();

        // داده‌های نمودار مشتریان (6 ماه اخیر)
        $customerLabels = [];
        $customerData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $customerLabels[] = $date->format('Y/m');
            $customerData[] = Customer::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }

        // داده‌های نمودار وضعیت اشتراک‌ها
        $subscriptionStatusData = [
            'active' => $activeSubscriptions,
            'expired' => $expiredSubscriptions,
            'inactive' => $totalSubscriptions - $activeSubscriptions - $expiredSubscriptions
        ];

        return view('back.reports.index', compact(
            'totalCustomers', 'activeCustomers', 'totalUpdates',
            'publishedUpdates', 'activeSubscriptions', 'expiredSubscriptions',
            'customerLabels', 'customerData', 'subscriptionStatusData'
        ));
    }

    public function customers(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $customers = $query->orderByDesc('created_at')->paginate(15);

        return view('back.reports.customers', compact('customers'));
    }

    public function updates(Request $request)
    {
        $query = Update::with('project');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $updates = $query->orderByDesc('created_at')->paginate(15);

        // لیست پروژه‌ها برای فیلتر
        $projects = \App\Models\Project::all();

        return view('back.reports.updates', compact('updates', 'projects'));
    }

    public function sales(Request $request)
    {
        $query = Subscription::with(['customer', 'project']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        $subscriptions = $query->orderByDesc('created_at')->paginate(15);

        // محاسبه درآمد کل (فرض بر این است که فیلد price وجود دارد یا از رابطه پروژه گرفته می‌شود)
        // در اینجا یک جمع‌بندی ساده انجام می‌دهیم
        $totalRevenue = 0;
        // اگر مدل Subscription فیلد price داشت: $subscriptions->sum('price');

        return view('back.reports.sales', compact('subscriptions', 'totalRevenue'));
    }
}
