<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;

Route::get('/teste', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API está funcionando corretamente',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::apiResource('orders', OrderController::class);

Route::get('/status', function () {
    return response()->json([
        'status' => 'operational',
        'version' => '1.0',
        'laravel_version' => app()->version()
    ]);
});