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
        // دریافت توکن (کد آپدیت) از هدر یا کوئری پارامتر
        // پکیج معمولاً توکن را در هدر Authorization یا به صورت کوئری می‌فرستد
        $token = $request->input('token')
            ?? $request->header('Authorization')
            ?? str_replace('Bearer ', '', $request->header('Authorization', ''));

        if (!$token) {
            return response()->json([
                'version' => null,
                'download_url' => null,
                'message' => 'کد آپدیت ارسال نشده است.'
            ], 403);
        }

        // پیدا کردن مشتری بر اساس کد آپدیت
        $customer = Customer::where('update_code', $token)->first();

        if (!$customer) {
            return response()->json([
                'version' => null,
                'download_url' => null,
                'message' => 'کد آپدیت نامعتبر است.'
            ], 404);
        }

        if ($customer->status !== 'active') {
            return response()->json([
                'version' => null,
                'download_url' => null,
                'message' => 'حساب کاربری شما غیرفعال است.'
            ], 403);
        }

        // بررسی اشتراک فعال
        // فرض بر این است که هر مشتری حداقل یک اشتراک فعال برای دریافت آپدیت دارد
        // اگر پروژه‌های مختلف دارید، می‌توانید project_id را هم چک کنید
        $activeSubscription = Subscription::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$activeSubscription) {
            return response()->json([
                'version' => null,
                'download_url' => null,
                'message' => 'اشتراک فعالی برای دریافت آپدیت ندارید.'
            ], 403);
        }

        // پیدا کردن آخرین آپدیت منتشر شده برای پروژه مربوطه (اگر پروژه مشخص است)
        // اگر چند پروژه دارید، بهتر است پکیج self-updater پروژه مورد نظر را هم بفرستد
        // در اینجا ساده‌ترین حالت: آخرین آپدیت منتشر شده کلی یا مربوط به پروژه اشتراک
        $projectId = $activeSubscription->project_id ?? null;

        $query = Update::where('status', 'active');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $latestUpdate = $query->orderByDesc('version')->first();

        if (!$latestUpdate) {
            return response()->json([
                'version' => null,
                'download_url' => null,
                'message' => 'آپدیتی یافت نشد.'
            ]);
        }

        // ساخت لینک دانلود امن (همان روتی که قبلاً ساختیم)
        // فرض بر این است که روت دانلود شما به صورت /download/{code} است
        // اگر روت دیگری دارید، آن را جایگزین کنید
        $downloadUrl = route('api.update.download', ['code' => $customer->update_code, 'update_id' => $latestUpdate->id]);

        return response()->json([
            'version' => $latestUpdate->version,
            'download_url' => $downloadUrl,
            'published_at' => $latestUpdate->created_at->toIso8601String(),
            'message' => 'آپدیت جدید موجود است.'
        ]);
    }

    public function download($code, $updateId)
    {
        // این متد مشابه کنترلر قبلی شماست اما مخصوص API
        $customer = Customer::where('update_code', $code)->first();

        if (!$customer || $customer->status !== 'active') {
            abort(403, 'دسترسی غیرمجاز');
        }

        // بررسی مجدد اشتراک (اختیاری ولی توصیه می‌شود برای امنیت بیشتر)
        $hasActiveSub = Subscription::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
            ->exists();

        if (!$hasActiveSub) {
            abort(403, 'اشتراک فعال نیست');
        }

        $update = Update::where('id', $updateId)->where('status', 'active')->firstOrFail();

        if (!$update->download_link || !Storage::disk('local')->exists($update->download_link)) {
            abort(404, 'فایل یافت نشد');
        }

        $filePath = str_replace('/storage/', '', $update->download_link);

        return Storage::disk('public')->download($filePath, $update->title . '_v' . $update->version . '.zip');
    }
}
