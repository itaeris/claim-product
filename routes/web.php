<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LimitController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:10,1');
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth');
Route::get('/api/me', [SessionController::class, 'show']);
Route::get('/invoices/{claim}', [InvoiceController::class, 'show'])->middleware('auth');

Route::middleware('auth')->prefix('api')->group(function () {
    Route::patch('/password', [SessionController::class, 'updatePassword']);
    Route::get('/catalog', [CatalogController::class, 'index']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{product}', [CartController::class, 'update']);
    Route::delete('/cart/items/{product}', [CartController::class, 'destroy']);
    Route::get('/claims', [ClaimController::class, 'index']);
    Route::post('/claims', [ClaimController::class, 'store']);
    Route::post('/claims/{claim}/payments', [PaymentController::class, 'store']);
    Route::post('/claims/{claim}/received', [ClaimController::class, 'received']);
    Route::get('/payments/{payment}/proof', [PaymentController::class, 'proof']);
    Route::get('/limits', [LimitController::class, 'index']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);

    Route::middleware('admin')->group(function () {
        Route::get('/brands', [BrandController::class, 'index']);
        Route::patch('/brands/{brand}/terms', [BrandController::class, 'updateTerms']);
        Route::apiResource('categories', CategoryController::class)->except('show');
        Route::patch('/products/{product}/price', [ProductController::class, 'updatePrice']);
        Route::apiResource('products', ProductController::class)->except('show');
        Route::apiResource('users', UserController::class)->except('show');
        Route::get('/claims/all', [ClaimController::class, 'adminIndex']);
        Route::post('/claims/{claim}/actions', [ClaimController::class, 'action']);
    });
});

Route::view('/{any?}', 'app')->where('any', '.*');
