<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * نمایش لیست اشتراک‌ها
     */
    public function index()
    {
        $subscriptions = []; // لیست اشتراک‌ها (بعداً از دیتابیس می‌آید)
        return view('admin.subscriptions', compact('subscriptions'));
    }

    /**
     * فرم ایجاد اشتراک جدید
     */
    public function create()
    {
        return view('admin.subscriptions-create');
    }

    /**
     * ذخیره اشتراک جدید
     */
    public function store(Request $request)
    {
        // اعتبارسنجی و ذخیره در دیتابیس
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        // کد ذخیره‌سازی در دیتابیس اینجا قرار می‌گیرد

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'اشتراک با موفقیت ایجاد شد.');
    }

    /**
     * نمایش جزئیات یک اشتراک
     */
    public function show($id)
    {
        // دریافت اشتراک از دیتابیس
        return view('admin.subscriptions-show', compact('id'));
    }

    /**
     * فرم ویرایش اشتراک
     */
    public function edit($id)
    {
        // دریافت اشتراک از دیتابیس
        return view('admin.subscriptions-edit', compact('id'));
    }

    /**
     * به‌روزرسانی اشتراک
     */
    public function update(Request $request, $id)
    {
        // اعتبارسنجی و به‌روزرسانی در دیتابیس
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        // کد به‌روزرسانی در دیتابیس اینجا قرار می‌گیرد

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'اشتراک با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف اشتراک
     */
    public function destroy($id)
    {
        // حذف از دیتابیس

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'اشتراک با موفقیت حذف شد.');
    }
}
