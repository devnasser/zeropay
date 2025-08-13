<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

// Home & Static Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');

// Language Switcher
Route::get('/locale/{locale}', [HomeController::class, 'setLocale'])->name('locale.switch');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

// Products
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{product:sku}', [ProductController::class, 'show'])->name('products.show');
Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');

// Shops
Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
Route::get('/shop/{shop:slug}', [ShopController::class, 'show'])->name('shops.show');

// Cart (Guest & Authenticated)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update/{item}', [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{item}', [CartController::class, 'remove'])->name('remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
});

// Authentication Required Routes
Route::middleware(['auth'])->group(function () {
    // User Dashboard
    Route::get('/dashboard', function () {
        $userType = auth()->user()->type;
        return redirect()->route($userType . '.dashboard');
    })->name('dashboard');
    
    // Customer Routes
    Route::prefix('customer')->name('customer.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders', [App\Http\Controllers\Customer\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [App\Http\Controllers\Customer\OrderController::class, 'show'])->name('orders.show');
        Route::get('/favorites', [App\Http\Controllers\Customer\FavoriteController::class, 'index'])->name('favorites.index');
        Route::get('/addresses', [App\Http\Controllers\Customer\AddressController::class, 'index'])->name('addresses.index');
        Route::get('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('profile.update');
    });
    
    // Shop Owner Routes
    Route::middleware(['can:manage-shop'])->prefix('shop')->name('shop.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Shop\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', App\Http\Controllers\Shop\ProductController::class);
        Route::resource('orders', App\Http\Controllers\Shop\OrderController::class)->only(['index', 'show', 'update']);
        Route::get('/settings', [App\Http\Controllers\Shop\SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [App\Http\Controllers\Shop\SettingsController::class, 'update'])->name('settings.update');
        Route::get('/analytics', [App\Http\Controllers\Shop\AnalyticsController::class, 'index'])->name('analytics');
    });
    
    // Admin Routes
    Route::middleware(['can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', App\Http\Controllers\Admin\UserController::class);
        Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
        Route::resource('shops', App\Http\Controllers\Admin\ShopController::class);
        Route::resource('products', App\Http\Controllers\Admin\ProductController::class);
        Route::resource('orders', App\Http\Controllers\Admin\OrderController::class);
        Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    });
});

// Authentication Routes
require __DIR__.'/auth.php';
