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
        $token = $request->query('token');

        // دیباگ مرحله 1
        $customer = Customer::where('update_code', $token)->first();
        if (!$customer) {
            return response("Customer not found for token: $token", 403);
        }

        if ($customer->status !== 'active') {
            return response("Customer status is: {$customer->status}", 403);
        }

        // دیباگ مرحله 2
        $hasActiveSubscription = $customer->subscriptions()
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        if (!$hasActiveSubscription) {
            return response("No active subscription found", 403);
        }

        // دیباگ مرحله 3
        $projectIds = $customer->subscriptions()->pluck('project_id');
        $latestUpdate = \App\Models\Update::whereIn('project_id', $projectIds)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->first();

        if (!$latestUpdate) {
            return response("No published updates found for projects: " . implode(',', $projectIds->toArray()));
        }

        // اگر همه چیز درست بود، HTML را برگردانید
        $downloadUrl = route('api.update.download', ['token' => $token, 'file' => $latestUpdate->id]);

        $html = <<<HTML
    <div class="release">
        <h2 class="release-title">
            <span class="css-truncate-target">v{$latestUpdate->version}</span>
        </h2>
        <div class="release-body">
            <p>{$latestUpdate->description}</p>
            <a href="{$downloadUrl}" class="btn">Download</a>
        </div>
    </div>
    HTML;

        return response($html)->header('Content-Type', 'text/html');
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
        return Storage::disk('local')->download($filePath, $update->title . '_v' . $update->version . '.zip');
        //return Storage::disk('public')->download($filePath, $update->title . '_v' . $update->version . '.zip');
    }
}
