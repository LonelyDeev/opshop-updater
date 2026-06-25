<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Update as UpdateModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UpdateController extends Controller
{
    // 1. بررسی نسخه (Check for Updates)
    public function check(Request $request)
    {
        $token = $request->input('token');
        $currentVersion = $request->input('version'); // مثلا 1.0.0

        // اعتبارسنجی مشتری و اشتراک
        $customer = Customer::where('update_code', $token)->first();

        if (!$customer || $customer->status !== 'active') {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        $hasActiveSubscription = $customer->subscriptions()
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        if (!$hasActiveSubscription) {
            return response()->json(['error' => 'No active subscription'], 403);
        }

        // پیدا کردن آخرین آپدیت منتشر شده برای پروژه‌های این مشتری
        $projectIds = $customer->subscriptions()->pluck('project_id');

        $latestUpdate = UpdateModel::whereIn('project_id', $projectIds)
            ->where('status', 'published')
            ->orderByDesc('version') // فرض بر این است که ورژن به صورت صحیح یا رشته قابل مقایسه ذخیره شده
            ->first();

        if (!$latestUpdate) {
            return response()->json(['update_available' => false]);
        }

        // مقایسه نسخه‌ها (نیاز به کتابخانه version_compare دارد)
       /* if (version_compare($latestUpdate->version, $currentVersion, '<=')) {
            return response()->json(['update_available' => false]);
        }*/

        // ساخت لینک دانلود امن (امضا دار یا موقت)
        // ما از روت download استفاده می‌کنیم که توکن را چک میکند
        $downloadUrl = route('api.update.download', [
            'token' => $token,
            'update_id' => $latestUpdate->id
        ]);

        return response()->json([
            'update_available' => true,
            'version' => $latestUpdate->version,
            'description' => $latestUpdate->description,
            'download_url' => $downloadUrl,
            'file_size' => $latestUpdate->file_size ?? null
        ]);
    }

    // 2. دانلود امن فایل (Secure Download)
    public function download(Request $request, $updateId)
    {
        $token = $request->input('token');

        // تکرار بررسی امنیت (خیلی مهم)
        $customer = Customer::where('update_code', $token)->first();
        if (!$customer || $customer->status !== 'active') {
            abort(403, 'Unauthorized');
        }

        // بررسی مجدد اشتراک (برای اطمینان صددرصد)
        // ... (کد بررسی اشتراک مثل بالا) ...

        $update = UpdateModel::findOrFail($updateId);

        // مسیر فایل در دیسک local (storage/app/public/updates/...)
        // فرض کنیم در دیتابیس فقط نام فایل ذخیره شده: update_v1.2.zip
        $filePath = 'updates/' . $update->file_name;

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'File not found');
        }

        // دانلود فایل بدون نمایش آدرس اصلی
        return Storage::disk('public')->download($filePath, $update->file_name);
    }
}
