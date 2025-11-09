<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AlumuniumInvoiceController;
use App\Http\Controllers\TransactionCategoryController;
use App\Http\Controllers\Report\SalesReportController;
use App\Http\Controllers\Report\ExpenseReportController;
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

    // Route Alumunium Invoice
    Route::get('/alumunium-invoice', [AlumuniumInvoiceController::class, 'index'])->name('alumunium-invoice.index');
    Route::get('/alumunium-invoice/next-number', [AlumuniumInvoiceController::class, 'getNextInvoiceNumber'])->name('alumunium-invoice.getNextNumber');
    Route::post('/alumunium-invoice', [AlumuniumInvoiceController::class, 'store'])->name('alumunium-invoice.store');
    Route::get('/alumunium-invoice/{alumunium_invoice}/edit', [AlumuniumInvoiceController::class, 'edit'])->name('alumunium-invoice.edit')->where('alumunium_invoice', '.*');
    Route::put('/alumunium-invoice/{alumunium_invoice}', [AlumuniumInvoiceController::class, 'update'])->name('alumunium-invoice.update')->where('alumunium_invoice', '.*');
    Route::delete('/alumunium-invoice/destroy-selected', [AlumuniumInvoiceController::class, 'destroySelected'])->name('alumunium-invoice.destroySelected');

    // Alumunium Invoice Print Routes
    Route::get('/alumunium-invoice/{invoice_number}/print/pdf', [AlumuniumInvoiceController::class, 'printPdf'])->name('alumunium-invoice.print.pdf')->where('invoice_number', '.*');
    Route::get('/alumunium-invoice/{invoice_number}/print/excel', [AlumuniumInvoiceController::class, 'printExcel'])->name('alumunium-invoice.print.excel')->where('invoice_number', '.*');

    // Route Sales Report
    Route::get('/sales-report', [SalesReportController::class, 'index'])->name('sales-report.index');
    Route::post('/sales-report', [SalesReportController::class, 'store'])->name('sales-report.store');
    Route::put('/sales-report/{id_sales_report}', [SalesReportController::class, 'update'])->name('sales-report.update');
    Route::patch('/sales-report/{id_sales_report}/status', [SalesReportController::class, 'updateStatus'])->name('sales-report.updateStatus');
    Route::delete('/sales-report/destroy-selected', [SalesReportController::class, 'destroySelected'])->name('sales-report.destroySelected');

    // Export routes
    Route::get('/sales-report/export/excel', [SalesReportController::class, 'exportExcel'])->name('sales-report.export.excel');
    Route::get('/sales-report/export/pdf', [SalesReportController::class, 'exportPdf'])->name('sales-report.export.pdf');

    // Route Expense Report
    Route::get('/expense-report', [ExpenseReportController::class, 'index'])->name('expense-report.index');
    Route::post('/expense-report', [ExpenseReportController::class, 'store'])->name('expense-report.store');
    Route::put('/expense-report/{id}', [ExpenseReportController::class, 'update'])->name('expense-report.update');
    Route::delete('/expense-report/{id}', [ExpenseReportController::class, 'destroy'])->name('expense-report.destroy');
    Route::delete('/expense-report/destroy-selected', [ExpenseReportController::class, 'destroySelected'])->name('expense-report.destroySelected');

    // Expense Report Export routes
    Route::get('/expense-report/export/excel', [ExpenseReportController::class, 'exportExcel'])->name('expense-report.export.excel');
    Route::get('/expense-report/export/pdf', [ExpenseReportController::class, 'exportPdf'])->name('expense-report.export.pdf');

    // Route Transaction Category
    Route::get('/transaction-category', [TransactionCategoryController::class, 'index'])->name('transaction-category.index');
    Route::post('/transaction-category', [TransactionCategoryController::class, 'store'])->name('transaction-category.store');
    Route::put('/transaction-category/{id}', [TransactionCategoryController::class, 'update'])->name('transaction-category.update');
    Route::patch('/transaction-category/{id}/toggle-status', [TransactionCategoryController::class, 'toggleStatus'])->name('transaction-category.toggleStatus');
    Route::delete('/transaction-category/destroy-selected', [TransactionCategoryController::class, 'destroySelected'])->name('transaction-category.destroySelected');

});