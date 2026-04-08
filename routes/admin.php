<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/edit/{id}', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('/products/update/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/delete/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::post('/inventory/add-stock', [InventoryController::class, 'addStock'])
        ->name('inventory.addStock');
    Route::post('/inventory/deduct-stock', [InventoryController::class, 'deductStock'])
        ->name('inventory.deductStock');
    Route::get('/inventory/stock-levels', [InventoryController::class, 'stockLevels'])
        ->name('inventory.stockLevels');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])
        ->name('inventory.movements');
    Route::get('/inventory/alerts', [InventoryController::class, 'alerts'])
        ->name('inventory.alerts');
    Route::post('/inventory/products/{product}/toggle-lock', [InventoryController::class, 'toggleLock'])
        ->name('inventory.toggleLock');
    Route::post('/inventory/products/{product}/threshold', [InventoryController::class, 'updateThreshold'])
        ->name('inventory.threshold');

    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/store', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::post('/update/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/delete/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/store', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('/update/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/delete/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('purchaseOrders.index');
        Route::post('/store', [PurchaseOrderController::class, 'store'])->name('purchaseOrders.store');
        Route::post('/pay/{purchaseOrder}', [PurchaseOrderController::class, 'pay'])->name('purchaseOrders.pay');
        Route::post('/receive/{purchaseOrder}', [PurchaseOrderController::class, 'receive'])->name('purchaseOrders.receive');
        Route::post('/cancel/{purchaseOrder}', [PurchaseOrderController::class, 'cancel'])->name('purchaseOrders.cancel');
    });

    Route::prefix('sales')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->name('sales.index');
        Route::get('/history', [SalesController::class, 'history'])->name('sales.history');
        Route::post('/store', [SalesController::class, 'store'])->name('sales.store');
        Route::post('/customers/store', [SalesController::class, 'storeCustomer'])->name('sales.customers.store');
        Route::post('/promotions/store', [SalesController::class, 'storePromotion'])->name('sales.promotions.store');
        Route::get('/{sale}', [SalesController::class, 'show'])->name('sales.show');
        Route::get('/{sale}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt');
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/store', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::post('/update/{id}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/data', [ReportController::class, 'data'])->name('reports.data');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::get('/accounting-snapshot', [ReportController::class, 'accountingSnapshot'])
            ->name('reports.accountingSnapshot');
        Route::post('/ledgers/store', [ReportController::class, 'storeLedger'])->name('reports.ledgers.store');
        Route::post('/transactions/store', [ReportController::class, 'storeTransaction'])
            ->name('reports.transactions.store');
    });

});
