<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // آمار و گزارش‌ها را می‌توانید از دیتابیس بگیرید
        $reports = [
            'total_revenue' => '50,000,000 تومان',
            'new_customers' => 15,
            'active_subscriptions' => 45,
            'updates_this_month' => 8,
        ];

        return view('back.reports', compact('reports'));
    }

    /**
     * گزارش فروش
     */
    public function sales(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // منطق گزارش فروش اینجا قرار می‌گیرد

        return view('back.reports-sales', compact('startDate', 'endDate'));
    }

    /**
     * گزارش مشتریان
     */
    public function customers(Request $request)
    {
        $type = $request->input('type', 'all');

        // منطق گزارش مشتریان اینجا قرار می‌گیرد

        return view('back.reports-customers', compact('type'));
    }

    /**
     * گزارش آپدیت‌ها
     */
    public function updates(Request $request)
    {
        $period = $request->input('period', 'month');

        // منطق گزارش آپدیت‌ها اینجا قرار می‌گیرد

        return view('back.reports-updates', compact('period'));
    }
}
