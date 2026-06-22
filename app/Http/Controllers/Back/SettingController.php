<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        // دریافت تنظیمات عمومی
        $generalSettings = Setting::where('group', 'general')->get()->pluck('value', 'key');

        // دریافت تنظیمات ایمیل
        $emailSettings = Setting::where('group', 'email')->get()->pluck('value', 'key');

        return view('back.settings.index', compact('generalSettings', 'emailSettings'));
    }

    public function update(Request $request)
    {
        // ذخیره تنظیمات عمومی
        if ($request->has('general')) {
            foreach ($request->general as $key => $value) {
                Setting::set($key, $value, 'string', 'general');
            }
        }

        // ذخیره تنظیمات ایمیل
        if ($request->has('email')) {
            foreach ($request->email as $key => $value) {
                // اگر چک‌باکس است و تیک نخورده، مقدار false یا 0 باشد
                if ($key === 'mail_enabled' && !$value) {
                    $value = 0;
                }
                Setting::set($key, $value, 'string', 'email');
            }
        }

        // پاک کردن کش تنظیمات
        Cache::forget('settings');

        return redirect()->route('settings.index')
            ->with('success', 'تنظیمات با موفقیت ذخیره شد.');
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');

        return redirect()->back()->with('success', 'کش سیستم با موفقیت پاک شد.');
    }

    public function optimize()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return redirect()->back()->with('success', 'سیستم بهینه‌سازی شد.');
    }
}
