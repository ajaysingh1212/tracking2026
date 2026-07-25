<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\LicensePlanController;
use App\Http\Controllers\Api\V1\UserLicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', AuthenticatedUserController::class)->name('me');
        Route::apiResource('license-plans', LicensePlanController::class)->only(['index', 'store']);
        Route::apiResource('user-licenses', UserLicenseController::class)->only(['index', 'store']);
    });
});
