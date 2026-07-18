<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
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

    public function showGateways()
    {
        //$this->authorize('settings.gateway');

        foreach (config('general.supported_gateways') as $key => $name) {
            Gateway::firstOrCreate(
                [
                    'key' => $key
                ],
                [
                    'name' => $name
                ]
            );
        }

        $gateways = Gateway::get();

        return view('back.settings.gateways', compact('gateways'));
    }

    public function updateGateways(Request $request)
    {
        //$this->authorize('settings.gateway');

        $active_ids = [];
        $allChanges = []; // برای ذخیره تمام تغییرات

        if ($request->gateways) {
            foreach ($request->gateways as $id => $request_gateway) {
                if (!isset($request_gateway['is_active'])) {
                    continue;
                }

                $active_ids[] = $id;
                $gateway = Gateway::find($id);

                if (!$gateway) {
                    continue;
                }

                // ذخیره مقادیر قدیمی
                $oldData = [
                    'name' => $gateway->name,
                    'ordering' => $gateway->ordering,
                    'is_active' => $gateway->is_active,
                    'configs' => []
                ];

                // ذخیره مقادیر قدیمی configs
                foreach ($gateway->configs as $config) {
                    $oldData['configs'][$config->key] = $config->value;
                }

                // مقادیر جدید
                $newData = [
                    'name' => $request_gateway['name'],
                    'ordering' => $request_gateway['ordering'],
                    'is_active' => true,
                    'configs' => $request_gateway['configs'] ?? []
                ];

                // بررسی تغییرات
                $gatewayChanges = [];

                if ($oldData['name'] != $newData['name']) {
                    $gatewayChanges['name'] = ['old' => $oldData['name'], 'new' => $newData['name']];
                }

                if ($oldData['ordering'] != $newData['ordering']) {
                    $gatewayChanges['ordering'] = ['old' => $oldData['ordering'], 'new' => $newData['ordering']];
                }

                if ($oldData['is_active'] != $newData['is_active']) {
                    $gatewayChanges['is_active'] = ['old' => $oldData['is_active'] ? 'فعال' : 'غیرفعال', 'new' => $newData['is_active'] ? 'فعال' : 'غیرفعال'];
                }

                // بررسی تغییرات configs
                foreach ($newData['configs'] as $key => $newValue) {
                    $oldValue = $oldData['configs'][$key] ?? null;
                    if ($oldValue != $newValue) {
                        $gatewayChanges['configs'][$key] = ['old' => $oldValue, 'new' => $newValue];
                    }
                }

                // بررسی configs حذف شده
                foreach ($oldData['configs'] as $key => $oldValue) {
                    if (!isset($newData['configs'][$key])) {
                        $gatewayChanges['configs'][$key] = ['old' => $oldValue, 'new' => 'حذف شده'];
                    }
                }

                // اگر تغییری وجود داشت، ذخیره کن
                if (!empty($gatewayChanges)) {
                    $allChanges[$gateway->name] = $gatewayChanges;
                }

                // انجام آپدیت
                $gateway->update([
                    'name' => $request_gateway['name'],
                    'ordering' => $request_gateway['ordering'],
                    'is_active' => true,
                ]);
                foreach ($request_gateway['configs'] as $key => $value) {
                    $gateway->configs()->updateOrCreate(
                        ['key' => $key],
                        ['value' => $value]
                    );
                }
            }
        }

        // غیرفعال کردن درگاه‌هایی که در لیست نیستند
        $deactivatedGateways = Gateway::whereNotIn('id', $active_ids)->get();
        foreach ($deactivatedGateways as $gateway) {
            if ($gateway->is_active) {
                $allChanges[$gateway->name] = [
                    'is_active' => ['old' => 'فعال', 'new' => 'غیرفعال']
                ];
            }
        }

        Gateway::whereNotIn('id', $active_ids)->update([
            'is_active' => false
        ]);

        // ثبت لاگ کامل


        return response()->json([
            'success'=>true
        ]);
    }
}
