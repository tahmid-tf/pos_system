<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/edit/{id}', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('/products/update/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/delete/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

});

Route::prefix('categories')->group(function () {

    Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/store', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::post('/update/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/delete/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

});
