<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\VoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
});

// Product Routes - Public
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/featured', [ProductController::class, 'featured']);
    Route::get('/deals', [ProductController::class, 'deals']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/{id}/similar', [ProductController::class, 'similar']);
});

// Voice Assistant
Route::prefix('voice')->middleware('auth:sanctum')->group(function () {
    Route::post('/process', [VoiceController::class, 'process']);
    Route::post('/search', [VoiceController::class, 'search']);
    Route::get('/commands', [VoiceController::class, 'commands']);
});

// Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Cart Management
    Route::prefix('cart')->group(function () {
        Route::get('/', 'App\Http\Controllers\Api\CartController@index');
        Route::post('/add', 'App\Http\Controllers\Api\CartController@add');
        Route::put('/update/{id}', 'App\Http\Controllers\Api\CartController@update');
        Route::delete('/remove/{id}', 'App\Http\Controllers\Api\CartController@remove');
        Route::delete('/clear', 'App\Http\Controllers\Api\CartController@clear');
    });
    
    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', 'App\Http\Controllers\Api\OrderController@index');
        Route::post('/', 'App\Http\Controllers\Api\OrderController@store');
        Route::get('/{id}', 'App\Http\Controllers\Api\OrderController@show');
        Route::post('/{id}/cancel', 'App\Http\Controllers\Api\OrderController@cancel');
        Route::get('/track/{code}', 'App\Http\Controllers\Api\OrderController@track');
    });
    
    // User Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', 'App\Http\Controllers\Api\ProfileController@show');
        Route::put('/', 'App\Http\Controllers\Api\ProfileController@update');
        Route::put('/password', 'App\Http\Controllers\Api\ProfileController@updatePassword');
        
        // Addresses
        Route::get('/addresses', 'App\Http\Controllers\Api\AddressController@index');
        Route::post('/addresses', 'App\Http\Controllers\Api\AddressController@store');
        Route::put('/addresses/{id}', 'App\Http\Controllers\Api\AddressController@update');
        Route::delete('/addresses/{id}', 'App\Http\Controllers\Api\AddressController@destroy');
    });
    
    // Favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/', 'App\Http\Controllers\Api\FavoriteController@index');
        Route::post('/{product_id}', 'App\Http\Controllers\Api\FavoriteController@toggle');
        Route::delete('/{product_id}', 'App\Http\Controllers\Api\FavoriteController@remove');
    });
    
    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::post('/', 'App\Http\Controllers\Api\ReviewController@store');
        Route::put('/{id}', 'App\Http\Controllers\Api\ReviewController@update');
        Route::delete('/{id}', 'App\Http\Controllers\Api\ReviewController@destroy');
    });
});

// Shop Routes
Route::prefix('shops')->group(function () {
    Route::get('/', 'App\Http\Controllers\Api\ShopController@index');
    Route::get('/{id}', 'App\Http\Controllers\Api\ShopController@show');
    Route::get('/{id}/products', 'App\Http\Controllers\Api\ShopController@products');
    Route::get('/{id}/reviews', 'App\Http\Controllers\Api\ShopController@reviews');
});

// Categories
Route::prefix('categories')->group(function () {
    Route::get('/', 'App\Http\Controllers\Api\CategoryController@index');
    Route::get('/{id}', 'App\Http\Controllers\Api\CategoryController@show');
    Route::get('/{id}/products', 'App\Http\Controllers\Api\CategoryController@products');
});

// Payment Routes
Route::prefix('payment')->middleware(['auth:sanctum', 'rate.limit:payment'])->group(function () {
    Route::post('/initialize', 'App\Http\Controllers\Api\PaymentController@initialize');
    Route::post('/confirm', 'App\Http\Controllers\Api\PaymentController@confirm');
    Route::get('/status/{reference}', 'App\Http\Controllers\Api\PaymentController@status');
    Route::post('/webhook', 'App\Http\Controllers\Api\PaymentController@webhook')->withoutMiddleware(['auth:sanctum']);
});