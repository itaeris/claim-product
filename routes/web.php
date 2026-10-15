<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth');

Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/me', [SessionController::class, 'show']);
    Route::get('/catalog', [CatalogController::class, 'index']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{product}', [CartController::class, 'update']);
    Route::delete('/cart/items/{product}', [CartController::class, 'destroy']);
    Route::get('/claims', [ClaimController::class, 'index']);
    Route::post('/claims', [ClaimController::class, 'store']);

    Route::middleware('admin')->group(function () {
        Route::get('/brands', [BrandController::class, 'index']);
        Route::apiResource('categories', CategoryController::class)->except('show');
        Route::patch('/products/{product}/price', [ProductController::class, 'updatePrice']);
        Route::apiResource('products', ProductController::class)->except('show');
        Route::apiResource('users', UserController::class)->except('show');
        Route::get('/discount', [DiscountController::class, 'show']);
        Route::patch('/discount', [DiscountController::class, 'update']);
        Route::get('/claims/all', [ClaimController::class, 'adminIndex']);
        Route::patch('/claims/{claim}', [ClaimController::class, 'updateStatus']);
    });
});

Route::view('/{any?}', 'app')->where('any', '.*');
