<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index()
    {
        // دریافت لاگ‌ها از فایل یا دیتابیس
        $logs = []; // در واقعیت از فایل storage/logs/laravel.log یا دیتابیس خوانده می‌شود

        return view('back.logs.index', compact('logs'));
    }

    /**
     * نمایش جزئیات یک لاگ
     */
    public function show($id)
    {
        // دریافت جزئیات لاگ
        return view('back.logs-show', compact('id'));
    }

    /**
     * دانلود لاگ‌ها
     */
    public function download()
    {
        // دانلود فایل لاگ
        $path = storage_path('logs/laravel.log');

        if (file_exists($path)) {
            return response()->download($path);
        }

        return redirect()->route('back.logs.index')
            ->with('error', 'فایل لاگ یافت نشد.');
    }

    /**
     * پاک کردن لاگ‌ها
     */
    public function clear()
    {
        // پاک کردن فایل لاگ
        $path = storage_path('logs/laravel.log');

        if (file_exists($path)) {
            file_put_contents($path, '');
        }

        return redirect()->route('back.logs.index')
            ->with('success', 'لاگ‌ها با موفقیت پاک شدند.');
    }
}
