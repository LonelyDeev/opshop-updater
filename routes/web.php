<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\DashboardController;
use App\Http\Controllers\Back\UpdateController;
use App\Http\Controllers\Back\CustomerController;
use App\Http\Controllers\Back\SubscriptionController;
use App\Http\Controllers\Back\ReportController;
use App\Http\Controllers\Back\LogController;
use App\Http\Controllers\Back\SettingController;
use App\Http\Controllers\Back\UserController;
use App\Http\Controllers\Back\ProjectController;
use App\Http\Controllers\Front\UpdateDownloadController;
use App\Http\Controllers\Api\UpdateCheckController;

Route::get('get-update/{code}', [UpdateDownloadController::class, 'download'])->name('public.download');
Route::get('/check-version', [UpdateCheckController::class, 'check'])->name('api.update.check');

// روت دانلود فایل (لینکی که در JSON برگردانده می‌شود)
Route::get('/download-update/{code}/{update_id}', [UpdateCheckController::class, 'download'])->name('api.update.download');
Auth::routes();


// مسیرهای مدیریت با احراز هویت
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified']) // افزودن verified در صورت نیاز
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // مدیریت کاربران (ادمین‌ها)
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // مدیریت مشتریان
        Route::resource('customers', CustomerController::class);

        // مدیریت اشتراک‌ها
        Route::resource('subscriptions', SubscriptionController::class)->except(['show']);
        Route::post('subscriptions/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('subscriptions.extend');

        // مدیریت آپدیت‌ها
        Route::resource('updates', UpdateController::class);

        // مدیریت پروژه‌ها
        Route::resource('projects', ProjectController::class);

        // گزارشات
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('customers', [ReportController::class, 'customers'])->name('customers');
            Route::get('updates', [ReportController::class, 'updates'])->name('updates');
            Route::get('sales', [ReportController::class, 'sales'])->name('sales');
        });

        // لاگ‌ها
        Route::prefix('logs')->name('logs.')->group(function () {
            Route::get('/', [LogController::class, 'index'])->name('index');
            Route::get('{id}', [LogController::class, 'show'])->name('show');
            Route::get('download', [LogController::class, 'download'])->name('download');
            Route::post('clear', [LogController::class, 'clear'])->name('clear');
        });

        // تنظیمات
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('/', [SettingController::class, 'update'])->name('update');
            Route::post('clear-cache', [SettingController::class, 'clearCache'])->name('clear-cache');
            Route::post('optimize', [SettingController::class, 'optimize'])->name('optimize');
        });
    });
