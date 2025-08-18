<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/recommendations', [ProductController::class, 'recommendations']);
Route::post('/products/search', [ProductController::class, 'search']);
Route::get('/brands', [ProductController::class, 'brands']);

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

// Shops
Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/{shop}', [ShopController::class, 'show']);
Route::get('/shops/{shop}/products', [ShopController::class, 'products']);

// Voice Assistant
Route::post('/voice/process', [VoiceController::class, 'process']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', [UserController::class, 'profile']);
    Route::put('/user', [UserController::class, 'update']);
    Route::post('/user/verify-phone', [UserController::class, 'verifyPhone']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/{cart}', [CartController::class, 'update']);
    Route::delete('/cart/{cart}', [CartController::class, 'remove']);
    Route::delete('/cart', [CartController::class, 'clear']);
    
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    
    // Addresses
    Route::get('/addresses', [UserController::class, 'addresses']);
    Route::post('/addresses', [UserController::class, 'storeAddress']);
    Route::put('/addresses/{address}', [UserController::class, 'updateAddress']);
    Route::delete('/addresses/{address}', [UserController::class, 'deleteAddress']);
    
    // Favorites
    Route::get('/favorites', [UserController::class, 'favorites']);
    Route::post('/favorites/{product}', [UserController::class, 'addFavorite']);
    Route::delete('/favorites/{product}', [UserController::class, 'removeFavorite']);
    
    // Reviews
    Route::post('/products/{product}/reviews', [ProductController::class, 'storeReview']);
    Route::put('/reviews/{review}', [ProductController::class, 'updateReview']);
    Route::delete('/reviews/{review}', [ProductController::class, 'deleteReview']);
});