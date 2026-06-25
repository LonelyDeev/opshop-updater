<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UpdateController;


Route::prefix('v1')->name('api.')->group(function () {

    Route::get('/check-update', [UpdateController::class, 'check'])->name('update.check');
    Route::get('/download-update/{updateId}', [UpdateController::class, 'download'])->name('update.download');
});
