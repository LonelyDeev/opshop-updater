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

        // ===== فیلتر دامنه =====
        $allowedDomain = $customer->website_url;
        $requestDomain = $request->getHost(); // دامنه درخواست فعلی

// اگر درخواست از localhost یا IP باشد، ممکن است پورت هم داشته باشد
        $requestFullDomain = $request->getHttpHost(); // شامل پورت: example.com:8080

        if (!$this->isDomainAllowed($allowedDomain, $requestFullDomain, $requestDomain)) {
            return response()->json([
                'error' => 'Access denied from this domain',
                'allowed' => $allowedDomain,
                'current' => $requestFullDomain
            ], 403);
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
            ->where(['status'=>'active'])
            ->orderByDesc('version') // فرض بر این است که ورژن به صورت صحیح یا رشته قابل مقایسه ذخیره شده
            ->first();

        if (!$latestUpdate) {
            return response()->json(['update_available' => false]);
        }

        // مقایسه نسخه‌ها (نیاز به کتابخانه version_compare دارد)
       if (version_compare($latestUpdate->version, $currentVersion, '<=')) {
            return response()->json(['update_available' => false]);
        }

        // ساخت لینک دانلود امن (امضا دار یا موقت)
        // ما از روت download استفاده می‌کنیم که توکن را چک میکند
        $downloadUrl = route('api.update.download', [
            'token' => $token,
            'updateId' => $latestUpdate->id
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

        $customer = Customer::where('update_code', $token)->first();
        if (!$customer || $customer->status !== 'active') {
            abort(403, 'Unauthorized');
        }

        // ===== فیلتر دامنه =====
        $allowedDomain = $customer->website_url;
        $requestDomain = $request->getHost(); // دامنه درخواست فعلی

// اگر درخواست از localhost یا IP باشد، ممکن است پورت هم داشته باشد
        $requestFullDomain = $request->getHttpHost(); // شامل پورت: example.com:8080

        if (!$this->isDomainAllowed($allowedDomain, $requestFullDomain, $requestDomain)) {
            return response()->json([
                'error' => 'Access denied from this domain',
                'allowed' => $allowedDomain,
                'current' => $requestFullDomain
            ], 403);
        }


        $update = UpdateModel::findOrFail($updateId);

        // ✅ از دیسک local استفاده کن (مسیر storage/app/private)
        if (!Storage::disk('local')->exists($update->download_link)) {
            abort(404, 'File not found');
        }

        // ✅ همان دیسک local برای دانلود
        return Storage::disk('local')->download($update->download_link, $update->file_name);
    }
}
