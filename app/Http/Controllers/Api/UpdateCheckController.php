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

        $customer = Customer::where('update_code', $token)->first();

        if (!$customer || $customer->status !== 'active') {
            abort(403);
        }

        $hasActiveSubscription = $customer->subscriptions()
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (!$hasActiveSubscription) {
            abort(403);
        }

        $projectIds = $customer->subscriptions()
            ->pluck('project_id');

        $updates = Update::whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->orderBy('version')
            ->get();

        $html = '<html><body>';

        foreach ($updates as $update) {

            $downloadUrl = route('api.update.download', [
                'code'      => $token,
                'update_id' => $update->id,
            ]);

            $html .= sprintf(
                '<a href="%s">webapp-v%s.zip</a><br>',
                $downloadUrl,
                $update->version
            );
        }

        $html .= '</body></html>';

        return response($html)
            ->header('Content-Type', 'text/html');
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
