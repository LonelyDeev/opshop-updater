<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class LogController extends Controller
{
    protected $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    public function index(Request $request)
    {
        if (!File::exists($this->logPath)) {
            return view('back.logs.index', ['logs' => [], 'currentLog' => null, 'error' => 'فایل لاگ یافت نشد.']);
        }

        // دریافت سطح لاگ برای فیلتر (اختیاری)
        $level = $request->get('level', 'all');

        // خواندن فایل لاگ
        $content = File::get($this->logPath);

        // تجزیه ساده لاگ‌ها (هر لاگ با تاریخ شروع می‌شود)
        // الگو: [2024-01-01 12:00:00] ...
        preg_match_all('/\[(.*?)\]\s+(\w+)\.(.*?):\s(.*?)(?=\n\[\d{4}-\d{2}-\d{2}|\z)/s', $content, $matches, PREG_SET_ORDER);

        $logs = [];
        foreach ($matches as $match) {
            $date = $match[1];
            $severity = $match[2]; // e.g., ERROR, WARNING
            $message = trim($match[4]);

            // فیلتر بر اساس سطح
            if ($level !== 'all' && strtolower($severity) !== strtolower($level)) {
                continue;
            }

            $logs[] = [
                'date' => $date,
                'level' => $severity,
                'message' => $message,
                'context' => '' // در صورت نیاز می‌توان کانتکست را هم استخراج کرد
            ];
        }

        // معکوس کردن برای نمایش جدیدترین‌ها اول
        $logs = array_reverse($logs);

        // صفحه‌بندی دستی (چون آرایه است)
        $perPage = 50;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($logs, ($currentPage - 1) * $perPage, $perPage);

        $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            count($logs),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('back.logs.index', compact('paginatedLogs', 'level'));
    }

    public function download()
    {
        if (!File::exists($this->logPath)) {
            abort(404, 'فایل لاگ یافت نشد.');
        }
        return Response::download($this->logPath, 'laravel.log');
    }

    public function clear()
    {
        if (File::exists($this->logPath)) {
            File::put($this->logPath, ''); // خالی کردن فایل به جای حذف، برای جلوگیری از مشکل دسترسی
        }
        return redirect()->route('admin.logs.index')->with('success', 'لاگ‌ها با موفقیت پاک شدند.');
    }
}

// کلاس کمکی برای صفحه‌بندی آرایه (اگر از لاراول ۹+ استفاده می‌کنید نیازی به تعریف جدا نیست اما برای اطمینان)
use Illuminate\Pagination\LengthAwarePaginator;
