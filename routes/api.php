<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\TabController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::group(['prefix' => 'auth'], function () {
    Route::get('/login/{provider}', [AuthController::class,'redirectToProvider']);
    Route::get('/login/{provider}/callback', [AuthController::class,'handleProviderCallback']);

    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});


Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::post('/add-locale', LanguageController::class)->name('translations');
    Route::post('/add-translation', TranslationController::class)->name('translations');

    Route::get('/social_categories', [SocialController::class, 'index']);
    Route::post('/social_categories/create', [SocialController::class, 'create']);

    Route::get('/tabs', [TabController::class, 'index']);
    Route::post('/tabs/create', [TabController::class, 'create']);

    Route::get('balances', 'App\Http\Controllers\BalanceController@index');
    Route::post('send-funds', 'App\Http\Controllers\TransactionController@sendFunds');
    Route::get('transaction-cost', 'App\Http\Controllers\TransactionController@getTransactionCost');

    Route::get('/wallets', [WalletController::class, 'index']);
    Route::get('/wallets/statistic', [StatisticController::class, 'index']);
    Route::post('/wallets/create', [WalletController::class, 'create']);
});
