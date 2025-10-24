<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AluminiumInvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


// Route::get('/login', [AuthController::class, 'showLogin'])->name(name: 'login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Website pages
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');

    //Route All Item
    Route::get('/item', [ItemController::class, 'index'])->name('item.index');
    Route::post('/item', [ItemController::class, 'store'])->name('item.store');
    Route::put('/item/{id_item}', [ItemController::class, 'update'])->name('item.update');
    Route::delete('/items', [ItemController::class, 'destroySelected'])->name('items.destroySelected');

    //Route Item Export
    Route::get('/item/export/pdf', [ItemController::class, 'exportPdf'])->name('item.export.pdf');
    Route::get('/item/export/excel', [ItemController::class, 'exportExcel'])->name('item.export.excel');

    // Route Aluminium Invoice
    Route::get('/aluminium-invoice', [AluminiumInvoiceController::class, 'index'])->name('aluminium-invoice.index');
    Route::get('/aluminium-invoice/next-number', [AluminiumInvoiceController::class, 'getNextInvoiceNumber'])->name('aluminium-invoice.getNextNumber');
    Route::post('/aluminium-invoice', [AluminiumInvoiceController::class, 'store'])->name('aluminium-invoice.store');
    Route::get('/aluminium-invoice/{aluminium_invoice}/edit', [AluminiumInvoiceController::class, 'edit'])->name('aluminium-invoice.edit')->where('aluminium_invoice', '.*');
    Route::put('/aluminium-invoice/{aluminium_invoice}', [AluminiumInvoiceController::class, 'update'])->name('aluminium-invoice.update')->where('aluminium_invoice', '.*');
    Route::delete('/aluminium-invoice/destroy-selected', [AluminiumInvoiceController::class, 'destroySelected'])->name('aluminium-invoice.destroySelected');
    // Route::delete('/aluminium-invoice/{aluminium_invoice}', [AluminiumInvoiceController::class, 'destroy'])->name('aluminium-invoice.destroy');
    // PDF/Excel export routes removed by request

});