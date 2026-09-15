<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/organizations/current', [OrganizationController::class, 'current']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::get('/organizations/{organization}/status', [OrganizationController::class, 'status']);
    Route::post('/organizations/{organization}/reparse', [OrganizationController::class, 'reparse']);
    Route::get('/organizations/{organization}/reviews', [ReviewController::class, 'index']);
});
