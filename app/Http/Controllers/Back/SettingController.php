<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    /**
     * نمایش صفحه تنظیمات
     */
    public function index()
    {
        // تنظیمات فعلی را می‌توانید از دیتابیس یا فایل config بگیرید
        $settings = [
            'site_name' => config('app.name'),
            'site_url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];

        return view('back.settings', compact('settings'));
    }

    /**
     * به‌روزرسانی تنظیمات عمومی
     */
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_url' => 'required|url',
            'timezone' => 'required|string',
            'locale' => 'required|string',
        ]);

        // ذخیره تنظیمات در دیتابیس یا فایل config
        // نکته: برای ذخیره در فایل config نیاز به دسترسی نوشتن دارید

        return redirect()->route('back.settings.index')
            ->with('success', 'تنظیمات با موفقیت به‌روزرسانی شد.');
    }

    /**
     * تنظیمات ایمیل
     */
    public function email()
    {
        $emailSettings = [
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),
            'mail_username' => config('mail.mailers.smtp.username'),
        ];

        return view('back.settings-email', compact('emailSettings'));
    }

    /**
     * به‌روزرسانی تنظیمات ایمیل
     */
    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer',
            'mail_encryption' => 'required|in:tls,ssl,null',
            'mail_username' => 'required|email',
            'mail_password' => 'nullable|string',
        ]);

        // ذخیره تنظیمات ایمیل

        return redirect()->route('back.settings.email')
            ->with('success', 'تنظیمات ایمیل با موفقیت به‌روزرسانی شد.');
    }

    /**
     * کش سیستم را پاک می‌کند
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return redirect()->route('back.settings.index')
            ->with('success', 'کش سیستم با موفقیت پاک شد.');
    }

    /**
     * بهینه‌سازی سیستم
     */
    public function optimize()
    {
        Artisan::call('optimize');

        return redirect()->route('back.settings.index')
            ->with('success', 'سیستم با موفقیت بهینه‌سازی شد.');
    }
}
