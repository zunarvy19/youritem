<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\CurrentUserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetTransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ShoppingController;
use App\Http\Controllers\WishlistItemController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->name('api.register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->name('api.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [CurrentUserController::class, 'show'])->name('api.user');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('api.logout');

    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('api.categories');

    Route::get('/wishlist-items', [WishlistItemController::class, 'index'])->name('api.wishlist-items.index');
    Route::post('/wishlist-items', [WishlistItemController::class, 'store'])->name('api.wishlist-items.store');
    Route::get('/wishlist-items/{wishlist_item}', [WishlistItemController::class, 'show'])->name('api.wishlist-items.show');
    Route::put('/wishlist-items/{wishlist_item}', [WishlistItemController::class, 'update'])->name('api.wishlist-items.update');
    Route::patch('/wishlist-items/{wishlist_item}/archive', [WishlistItemController::class, 'archive'])->name('api.wishlist-items.archive');
    Route::patch('/wishlist-items/{wishlist_item}/restore', [WishlistItemController::class, 'restore'])->name('api.wishlist-items.restore');

    Route::get('/budget', [BudgetController::class, 'show'])->name('api.budget.show');
    Route::put('/budget', [BudgetController::class, 'update'])->name('api.budget.update');
    Route::post('/budget/transactions', [BudgetTransactionController::class, 'store'])->name('api.budget-transactions.store');

    Route::get('/shopping/recommendations', [ShoppingController::class, 'recommend'])->name('api.shopping.recommend');

    Route::post('/wishlist-items/{wishlist_item}/purchase', [PurchaseController::class, 'store'])->name('api.wishlist-items.purchase');
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('api.purchases.index');
});
