<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Update;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UpdateCheckController extends Controller
{
    public function check(Request $request)
    {
        $token = $request->query('token'); // یا هدر

        // 1. بررسی اعتبار توکن و مشتری
        $customer = Customer::where('update_code', $token)->first();

        if (!$customer || $customer->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        // 2. بررسی اشتراک فعال
        $hasActiveSubscription = $customer->subscriptions()
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        if (!$hasActiveSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'اشتراک فعال نیست'
            ], 403);
        }

        // 3. دریافت آخرین آپدیت منتشر شده
        $projectIds = $customer->subscriptions()->pluck('project_id');

        $latestUpdate = \App\Models\Update::whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->first();

        if (!$latestUpdate) {
            return response()->json([
                'success' => true,
                'version_available' => null,
                'message' => 'هیچ آپدیتی موجود نیست'
            ]);
        }

        // بازگشت اطلاعات به صورت JSON که پکیج self-updater انتظار دارد
        return response()->json([
            'success' => true,
            'version_available' => $latestUpdate->version,
            'version_installed' => $request->input('version_installed', '1.0.0'), // نسخه نصب شده فعلی
            'download_link' => route('api.update.download', ['code' => $token, 'update_id' => $latestUpdate->id]),
            'release_notes' => $latestUpdate->description,
            'release_date' => $latestUpdate->created_at->toIso8601String(),
            'name' => $latestUpdate->title ?? 'آپدیت جدید'
        ]);
    }

    public function download($code, $updateId)
    {
        $customer = Customer::where('update_code', $code)->first();

        if (!$customer || $customer->status !== 'active') {
            abort(403, 'دسترسی غیرمجاز');
        }

        // بررسی مجدد اشتراک
        $hasActiveSub = Subscription::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        if (!$hasActiveSub) {
            abort(403, 'اشتراک فعال نیست');
        }

        $update = Update::where('id', $updateId)->where('status', 'active')->firstOrFail();

        // بررسی وجود فایل
        if (!$update->download_link || !Storage::disk('local')->exists($update->download_link)) {
            abort(404, 'فایل یافت نشد');
        }

        // تبدیل مسیر
        $filePath = str_replace('/storage/', '', $update->download_link);
        $fileName = $update->title . '_v' . $update->version . '.zip';

        return Storage::disk('local')->download($filePath, $fileName);
    }
}
