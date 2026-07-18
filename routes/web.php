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
use App\Http\Controllers\Back\PackageController;
use App\Http\Controllers\Back\PackageVersionController;
use App\Http\Controllers\Back\PackagePricingPlanController;
use App\Http\Controllers\Back\PackageLicenseController;
use App\Http\Controllers\Back\PackagePurchaseController;

Route::get('/',function(){
    return redirect('/admin');
});
Route::get('get-update/{code}', [UpdateDownloadController::class, 'download'])->name('public.download');



// مسیرهای مدیریت با احراز هویت
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified']) // افزودن verified در صورت نیاز
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
            Route::get('gateways', [SettingController::class, 'showGateways'])->name('gateways');
            Route::post('gateways', [SettingController::class, 'updateGateways'])->name('updateGateways');
        });


    Route::prefix('packages')->name('packages.')->group(function () {

        // --- پکیج‌ها (master) ---
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::get('create', [PackageController::class, 'create'])->name('create');
        Route::post('/', [PackageController::class, 'store'])->name('store');
        Route::get('{package}', [PackageController::class, 'show'])->name('show');
        Route::get('{package}/edit', [PackageController::class, 'edit'])->name('edit');
        Route::put('{package}', [PackageController::class, 'update'])->name('update');
        Route::delete('{package}', [PackageController::class, 'destroy'])->name('destroy');

        Route::delete('{package}/images/{image}', [PackageController::class, 'deleteImage'])->name('images.destroy');
        Route::post('{package}/images/reorder', [PackageController::class, 'reorderImages'])->name('images.reorder');


        // --- نسخه‌ها (detail) ---
        Route::get('{package}/versions', [PackageVersionController::class, 'index'])->name('versions.index');
        Route::get('{package}/versions/create', [PackageVersionController::class, 'create'])->name('versions.create');
        Route::post('{package}/versions', [PackageVersionController::class, 'store'])->name('versions.store');
        Route::get('{package}/versions/{version}', [PackageVersionController::class, 'show'])->name('versions.show');
        Route::get('{package}/versions/{version}/edit', [PackageVersionController::class, 'edit'])->name('versions.edit');
        Route::put('{package}/versions/{version}', [PackageVersionController::class, 'update'])->name('versions.update');
        Route::delete('{package}/versions/{version}', [PackageVersionController::class, 'destroy'])->name('versions.destroy');

        // --- طرح‌های قیمت‌گذاری ---
        Route::get('{package}/plans', [PackagePricingPlanController::class, 'index'])->name('plans.index');
        Route::get('{package}/plans/create', [PackagePricingPlanController::class, 'create'])->name('plans.create');
        Route::post('{package}/plans', [PackagePricingPlanController::class, 'store'])->name('plans.store');
        Route::get('{package}/plans/{plan}/edit', [PackagePricingPlanController::class, 'edit'])->name('plans.edit');
        Route::put('{package}/plans/{plan}', [PackagePricingPlanController::class, 'update'])->name('plans.update');
        Route::delete('{package}/plans/{plan}', [PackagePricingPlanController::class, 'destroy'])->name('plans.destroy');
    });

// --- لایسنس‌ها و خریدها (outside of {package} prefix) ---
    Route::prefix('package-licenses')->name('packages.licenses.')->group(function () {
        Route::get('/', [PackageLicenseController::class, 'index'])->name('index');
        Route::get('expire-old', [PackageLicenseController::class, 'expireOld'])->name('expire-old');
        Route::get('{license}', [PackageLicenseController::class, 'show'])->name('show');
        Route::post('{license}/revoke', [PackageLicenseController::class, 'revoke'])->name('revoke');
        Route::post('{license}/activate', [PackageLicenseController::class, 'activate'])->name('activate');
    });

    Route::prefix('package-purchases')->name('packages.purchases.')->group(function () {
        Route::get('/', [PackagePurchaseController::class, 'index'])->name('index');
        Route::get('{purchase}', [PackagePurchaseController::class, 'show'])->name('show');
    });

});
