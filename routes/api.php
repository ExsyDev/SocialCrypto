<?php

use App\Http\Controllers\SocialController;
use App\Http\Controllers\TabController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::get('/social_categories', [SocialController::class, 'index']);
    Route::post('/social_categories/create', [SocialController::class, 'create']);

    Route::get('/tabs', [TabController::class, 'index']);
    Route::post('/tabs/create', [TabController::class, 'create']);

    Route::get('/wallets', [WalletController::class, 'index']);
    Route::post('/wallets/create', [WalletController::class, 'create']);
});
