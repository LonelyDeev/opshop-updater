<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UpdateCheckController;


Route::prefix('v1')->name('api.')->group(function () {

    Route::get('/check-version', [UpdateCheckController::class, 'check'])->name('update.check');
    Route::get('/download-update/{code}/{update_id}', [UpdateCheckController::class, 'download'])->name('update.download');

});
