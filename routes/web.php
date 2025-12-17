<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Invoice\AlumuniumInvoiceController;
use App\Http\Controllers\Invoice\ProyekInvoiceController;
use App\Http\Controllers\Invoice\PaymentAccountController;
use App\Http\Controllers\Report\TransactionCategoryController;
use App\Http\Controllers\Report\SalesReportController;
use App\Http\Controllers\Report\ExpenseReportController;
use App\Http\Controllers\Sdm\EmployeeController;
use App\Http\Controllers\Sdm\AttendanceController;
use App\Http\Controllers\Sdm\OvertimeController;
use App\Http\Controllers\Sdm\PayrollController;
use App\Http\Controllers\Finance\ReimburseController;
use App\Http\Controllers\Administrasi\DocumentReceiptController;
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

    // Route Proyek Invoice
    Route::get('/proyek-invoice', [ProyekInvoiceController::class, 'index'])->name('proyek-invoice.index');
    Route::get('/proyek-invoice/next-number', [ProyekInvoiceController::class, 'getNextInvoiceNumber'])->name('proyek-invoice.getNextNumber');
    Route::post('/proyek-invoice', [ProyekInvoiceController::class, 'store'])->name('proyek-invoice.store');
    Route::get('/proyek-invoice/{proyek_invoice}/edit', [ProyekInvoiceController::class, 'edit'])->name('proyek-invoice.edit')->where('proyek_invoice', '.*');
    Route::put('/proyek-invoice/{proyek_invoice}', [ProyekInvoiceController::class, 'update'])->name('proyek-invoice.update')->where('proyek_invoice', '.*');
    Route::delete('/proyek-invoice/destroy-selected', [ProyekInvoiceController::class, 'destroySelected'])->name('proyek-invoice.destroySelected');

    // Proyek Invoice Print Routes
    Route::get('/proyek-invoice/{invoice_number}/print/pdf', [ProyekInvoiceController::class, 'printPdf'])->name('proyek-invoice.print.pdf')->where('invoice_number', '.*');
    Route::get('/proyek-invoice/{invoice_number}/print/excel', [ProyekInvoiceController::class, 'printExcel'])->name('proyek-invoice.print.excel')->where('invoice_number', '.*');

    // Route Payment Accounts
    Route::get('/payment-accounts', [PaymentAccountController::class, 'index'])->name('payment-accounts.index');
    Route::post('/payment-accounts', [PaymentAccountController::class, 'store'])->name('payment-accounts.store');
    Route::put('/payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'update'])->name('payment-accounts.update');
    Route::post('/payment-accounts/{paymentAccount}/toggle', [PaymentAccountController::class, 'toggleActive'])->name('payment-accounts.toggle');
    Route::delete('/payment-accounts/destroy-selected', [PaymentAccountController::class, 'destroySelected'])->name('payment-accounts.destroySelected');

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

    // ============================================
    // SDM (Sumber Daya Manusia) Routes
    // ============================================

    // Route Employee (Karyawan)
    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
    Route::post('/employee', [EmployeeController::class, 'store'])->name('employee.store');
    Route::put('/employee/{employee}', [EmployeeController::class, 'update'])->name('employee.update');
    Route::delete('/employee/destroy-selected', [EmployeeController::class, 'destroy'])->name('employee.destroy');

    // Route Attendance (Absensi)
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/destroy-selected', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

    // Route Overtime (Lembur)
    Route::get('/overtime', [OvertimeController::class, 'index'])->name('overtime.index');
    Route::post('/overtime', [OvertimeController::class, 'store'])->name('overtime.store');
    Route::put('/overtime/{overtime}', [OvertimeController::class, 'update'])->name('overtime.update');
    Route::delete('/overtime/destroy-selected', [OvertimeController::class, 'destroy'])->name('overtime.destroy');

    // Route Payroll (Penggajian)
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/export/excel', [PayrollController::class, 'exportExcel'])->name('payroll.export.excel');
    Route::get('/payroll/export/pdf', [PayrollController::class, 'exportPdf'])->name('payroll.export.pdf');
    Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
    Route::post('/payroll/check-attendance', [PayrollController::class, 'checkAttendanceCompleteness'])->name('payroll.check-attendance');
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
    Route::patch('/payroll/bulk-pay', [PayrollController::class, 'bulkPay'])->name('payroll.bulk-pay');
    Route::delete('/payroll/destroy-selected', [PayrollController::class, 'destroy'])->name('payroll.destroy');

    // ============================================
    // Finance (Keuangan) Routes
    // ============================================

    // Route Reimburse
    Route::get('/reimburse', [ReimburseController::class, 'index'])->name('reimburse.index');
    Route::post('/reimburse', [ReimburseController::class, 'store'])->name('reimburse.store');
    Route::put('/reimburse/{reimburse}', [ReimburseController::class, 'update'])->name('reimburse.update');
    Route::delete('/reimburse/destroy-selected', [ReimburseController::class, 'destroy'])->name('reimburse.destroy');

    // Route Reimburse - Approve/Reject (Super Admin)
    Route::post('/reimburse/approve', [ReimburseController::class, 'approve'])->name('reimburse.approve');
    Route::post('/reimburse/reject', [ReimburseController::class, 'reject'])->name('reimburse.reject');

    // Route Reimburse - Export
    Route::get('/reimburse/export/pdf', [ReimburseController::class, 'exportPdf'])->name('reimburse.export.pdf');
    Route::get('/reimburse/export/excel', [ReimburseController::class, 'exportExcel'])->name('reimburse.export.excel');

    // Route Reimburse - API untuk get total selected
    Route::post('/reimburse/get-selected-total', [ReimburseController::class, 'getSelectedTotal'])->name('reimburse.getSelectedTotal');

    // ============================================
    // Administrasi Routes
    // ============================================

    // Route Document Receipt (Tanda Terima Dokumen)
    Route::get('/document-receipt', [DocumentReceiptController::class, 'index'])->name('document-receipt.index');
    Route::post('/document-receipt', [DocumentReceiptController::class, 'store'])->name('document-receipt.store');
    Route::put('/document-receipt/{documentReceipt}', [DocumentReceiptController::class, 'update'])->name('document-receipt.update');
    Route::delete('/document-receipt/destroy-selected', [DocumentReceiptController::class, 'destroySelected'])->name('document-receipt.destroySelected');

    // Route Document Receipt - Export PDF
    Route::get('/document-receipt/export/pdf', [DocumentReceiptController::class, 'exportPdfAll'])->name('document-receipt.export.pdf');
    Route::post('/document-receipt/export/pdf-selected', [DocumentReceiptController::class, 'exportPdfSelected'])->name('document-receipt.export.pdf.selected');

});