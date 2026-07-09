<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UpdateController;
use App\Http\Controllers\Api\ApiPackageController;
use App\Http\Controllers\Api\ApiPaymentCallbackController;
use App\Http\Controllers\Api\ApiPackageDownloadController;
Route::prefix('v1')->name('api.')->group(function () {

    Route::get('/check-update', [UpdateController::class, 'check'])->name('update.check');
    Route::get('/download-update/{updateId}', [UpdateController::class, 'download'])->name('update.download');

    Route::name('packages.')->group(function () {

        // --- لیست و جزئیات ---
        Route::get('/packages', [ApiPackageController::class, 'index'])->name('index');
        Route::get('/packages/{slug}', [ApiPackageController::class, 'show'])->name('show');

        // --- خرید و پرداخت ---
        Route::post('/packages/{slug}/purchase', [ApiPackageController::class, 'purchase'])->name('purchase');
        Route::post('/payments/{transactionId}/verify', [ApiPackageController::class, 'verifyPayment'])->name('verify');

        // --- کال‌بک درگاه shetabit (کاربر از درگاه اینجا برمی‌گرده) ---
        Route::get('/payments/callback', [ApiPaymentCallbackController::class, 'callback'])
            ->name('payment.callback');
        Route::post('/payments/callback', [ApiPaymentCallbackController::class, 'callback'])
            ->name('payment.callback.post');

        // --- لایسنس ---
        Route::post('/packages/{slug}/verify-license', [ApiPackageController::class, 'verifyLicense'])->name('verify-license');

        // --- آپدیت ---
        Route::get('/packages/{slug}/check-update', [ApiPackageController::class, 'checkUpdate'])->name('check-update');

        // --- دانلود ---
        Route::get('/packages/download/{token}', [ApiPackageDownloadController::class, 'download'])->name('download');
    });

});
