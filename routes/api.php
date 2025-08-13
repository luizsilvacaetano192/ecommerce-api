<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;

Route::get('/', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API está funcionando corretamente',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::post('users', [UserController::class, 'store']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::put('users/{user}', [UserController::class, 'update']);
Route::delete('users/{user}', [UserController::class, 'destroy']);

Route::get('orders/{order}', [OrderController::class, 'show']);
Route::put('orders/{order}', [OrderController::class, 'update']);
Route::delete('orders/{order}', [OrderController::class, 'destroy']);

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
});

Route::get('/status', function () {
    return response()->json([
        'status' => 'operational',
        'version' => '1.0',
        'laravel_version' => app()->version()
    ]);
});
