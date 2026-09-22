<?php

use App\Http\Controllers\Front\UpdateDownloadController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// مسیر عمومی دانلود آپدیت (بدون احراز هویت)
Route::get('get-update/{code}', [UpdateDownloadController::class, 'download'])->name('public.download');

\Illuminate\Support\Facades\Auth::routes();

/*
|--------------------------------------------------------------------------
| پنل مدیریت – کامپوننت‌های تمام‌صفحه Livewire (SPA با wire:navigate)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    // محصولات
    Route::get('projects', \App\Livewire\Projects\Index::class)->name('projects.index');
    Route::get('updates', \App\Livewire\Updates\Index::class)->name('updates.index');
    Route::get('packages', \App\Livewire\Packages\Index::class)->name('packages.index');
    Route::get('packages/{package}', \App\Livewire\Packages\Show::class)->name('packages.show');

    // فروش و مشتریان
    Route::get('licenses', \App\Livewire\Licenses\Index::class)->name('licenses.index');
    Route::get('licenses/{license}', \App\Livewire\Licenses\Show::class)->name('licenses.show');
    Route::get('purchases', \App\Livewire\Purchases\Index::class)->name('purchases.index');
    Route::get('purchases/{purchase}', \App\Livewire\Purchases\Show::class)->name('purchases.show');
    Route::get('customers', \App\Livewire\Customers\Index::class)->name('customers.index');
    Route::get('subscriptions', \App\Livewire\Subscriptions\Index::class)->name('subscriptions.index');

    // سیستم
    Route::get('reports', \App\Livewire\Reports\Index::class)->name('reports.index');
    Route::get('logs', \App\Livewire\Logs\Index::class)->name('logs.index');
    Route::get('settings', \App\Livewire\Settings\Index::class)->name('settings.index');
    Route::get('settings/gateways', \App\Livewire\Gateways::class)->name('settings.gateways');
    Route::get('users', \App\Livewire\Users\Index::class)->name('users.index');
});
