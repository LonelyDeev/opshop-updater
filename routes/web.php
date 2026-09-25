<?php

use App\Http\Controllers\Front\UpdateDownloadController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| فروشگاه عمومی (Storefront)
|--------------------------------------------------------------------------
*/
Route::get('/', \App\Livewire\Shop\Home::class)->name('shop.home');
Route::get('packages/{slug}', \App\Livewire\Shop\PackageShow::class)->name('shop.package');

// طرح‌های اشتراک (فروش عمومی + نتیجه خرید اشتراک)
Route::get('subscriptions', \App\Livewire\Shop\SubscriptionsIndex::class)->name('shop.subscriptions');
Route::get('subscription/result/{order}', \App\Livewire\Shop\SubscriptionResult::class)->name('subscription.result');

// پرداخت (عمومی)
Route::get('payment/callback', [\App\Http\Controllers\Front\WebPaymentController::class, 'callback'])->name('payment.callback');
Route::post('payment/callback', [\App\Http\Controllers\Front\WebPaymentController::class, 'callback'])->name('payment.callback.post');
Route::get('payment/result/{purchase}', \App\Livewire\Shop\PaymentResult::class)->name('payment.result');

// صفحه «نتیجه پرداخت» (مرحله میانی پس از درگاه):
// وضعیت پرداخت + شمارش معکوس ۱۰ ثانیه‌ای + دکمه بازگشت به callback_url فروشگاه
Route::get('payment/return/{purchase}', [\App\Http\Controllers\Front\PaymentReturnController::class, 'show'])
    ->name('payment.return');

// همان صفحه برای سفارش‌های اشتراک (API با callback_url بیرونی)
Route::get('payment/return/subscription/{order}', [\App\Http\Controllers\Front\PaymentReturnController::class, 'showSubscription'])
    ->name('payment.return.subscription');

// فرم پرداخت درایورهای فرم‌محور + شبیه‌ساز درگاه آزمایشی (local)
// payment_url خریدهای API برای این درایورها به این مسیر امضادار اشاره می‌کند.
Route::get('payment/form/{purchase}', [\App\Http\Controllers\Front\PaymentFormController::class, 'show'])
    ->name('payment.form')
    ->middleware('signed');

// همان فرم برای سفارش‌های اشتراک
Route::get('payment/form/subscription/{order}', [\App\Http\Controllers\Front\PaymentFormController::class, 'showSubscription'])
    ->name('payment.form.subscription')
    ->middleware('signed');

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

    // طرح‌های اشتراک + درخواست‌ها/تأییدها
    Route::get('subscription-plans', \App\Livewire\Plans\Index::class)->name('plans.index');
    Route::get('subscription-orders', \App\Livewire\Subscriptions\Orders::class)->name('subscriptions.orders');

    // سیستم
    Route::get('reports', \App\Livewire\Reports\Index::class)->name('reports.index');
    Route::get('logs', \App\Livewire\Logs\Index::class)->name('logs.index');
    Route::get('settings', \App\Livewire\Settings\Index::class)->name('settings.index');
    Route::get('settings/gateways', \App\Livewire\Gateways::class)->name('settings.gateways');
    Route::get('users', \App\Livewire\Users\Index::class)->name('users.index');
});
