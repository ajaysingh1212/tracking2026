<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\Auth\TokenLoginController;
use App\Http\Controllers\Api\V1\Auth\TokenLogoutController;
use App\Http\Controllers\Api\V1\DiagnosticController;
use App\Http\Controllers\Api\V1\GpsLocationController;
use App\Http\Controllers\Api\V1\LicensePlanController;
use App\Http\Controllers\Api\V1\UserLicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', TokenLoginController::class)->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', AuthenticatedUserController::class)->name('me');
        Route::post('auth/logout', TokenLogoutController::class)->name('auth.logout');
        Route::apiResource('license-plans', LicensePlanController::class)->only(['index', 'store']);
        Route::apiResource('user-licenses', UserLicenseController::class)->only(['index', 'store']);
        Route::post('gps/locations', [GpsLocationController::class, 'store'])->name('gps.locations.store');
        Route::post('gps/locations/batch', [GpsLocationController::class, 'batchStore'])->name('gps.locations.batch');
        Route::post('gps/diagnostics', [DiagnosticController::class, 'store'])->name('gps.diagnostics.store');
    });
});
