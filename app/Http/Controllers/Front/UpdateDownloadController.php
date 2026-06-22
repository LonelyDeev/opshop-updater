<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Update;
use Illuminate\Support\Facades\Storage;

class UpdateDownloadController extends Controller
{
    /**
     * هندل کردن درخواست دانلود
     * آدرس نمونه: site.com/get-update/YOUR_UNIQUE_CODE
     */
    public function download($code)
    {
        // 1. پیدا کردن مشتری بر اساس کد آپدیت
        $customer = Customer::where('update_code', $code)->first();

        if (!$customer) {
            abort(404, 'کد آپدیت نامعتبر است.');
        }

        // 2. بررسی وضعیت مشتری
        if ($customer->status !== 'active') {
            abort(403, 'حساب کاربری شما غیرفعال است.');
        }

        // 3. بررسی اشتراک‌های فعال برای این مشتری
        // شرط: وضعیت فعال باشد، پرداخت شده باشد و تاریخ انقضا نگذشته باشد (یا null باشد یعنی دائمی)
        $validSubscription = Subscription::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$validSubscription) {
            abort(403, 'شما اشتراک معتبر و فعالی برای دریافت آپدیت ندارید.');
        }

        // 4. دریافت آخرین آپدیت منتشر شده برای پروژه‌ای که مشتری اشتراک دارد
        // فرض بر این است که هر اشتراک به یک پروژه متصل است (اگر مدل Subscription فیلد project_id دارد)
        // اگر اشتراک عمومی است، آخرین آپدیت کلی را می‌دهیم

        $query = Update::where('status', 'published');

        if (isset($validSubscription->project_id)) {
            $query->where('project_id', $validSubscription->project_id);
        }

        $latestUpdate = $query->orderByDesc('version')->first();

        if (!$latestUpdate || !$latestUpdate->file_path) {
            abort(404, 'هیچ فایلی برای دانلود یافت نشد.');
        }

        // 5. بررسی وجود فایل در دیسک خصوصی
        // فایل‌ها باید در storage/app/private/updates ذخیره شده باشند
        $filePath = $latestUpdate->file_path;

        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'فایل آپدیت در سرور یافت نشد.');
        }

        // 6. ثبت لاگ دانلود (اختیاری اما توصیه شده)
        // Log::channel('downloads')->info("Download by customer {$customer->email} for update {$latestUpdate->id}");

        // 7. استریم کردن فایل به صورت امن
        return Storage::disk('private')->download($filePath, $latestUpdate->title . '_v' . $latestUpdate->version . '.zip');

        // یا اگر می‌خواهید کنترل بیشتری روی هدرها داشته باشید:
        /*
        return new StreamedResponse(function () use ($filePath) {
            $stream = Storage::disk('private')->readStream($filePath);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => Storage::disk('private')->mimeType($filePath),
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Accel-Redirect' => '/protected-updates/' . $filePath, // اگر از Nginx استفاده می‌کنید
        ]);
        */
    }
}
