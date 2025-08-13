<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;

Route::get('/teste', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API está funcionando corretamente',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::apiResource('orders', OrderController::class);
Route::apiResource('users', UserController::class);
Route::post('login', [AuthController::class, 'login']);




Route::get('/status', function () {
    return response()->json([
        'status' => 'operational',
        'version' => '1.0',
        'laravel_version' => app()->version()
    ]);
});