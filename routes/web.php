<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Finance\AlumuniumInvoiceController;
use App\Http\Controllers\Finance\ProyekInvoiceController;
use App\Http\Controllers\Finance\PaymentAccountController;
use App\Http\Controllers\Finance\RecapSalesController;
use App\Http\Controllers\Finance\RecapExpenseController;
use App\Http\Controllers\Report\TransactionCategoryController;
use App\Http\Controllers\Report\SalesReportController;
use App\Http\Controllers\Report\ExpenseReportController;
use App\Http\Controllers\Sdm\EmployeeController;
use App\Http\Controllers\Sdm\AttendanceController;
use App\Http\Controllers\Sdm\OvertimeController;
use App\Http\Controllers\Sdm\PayrollController;
use App\Http\Controllers\Finance\ReimburseController;
use App\Http\Controllers\Administrasi\DocumentReceiptController;
use App\Http\Controllers\Administrasi\CashOutProofController;
use App\Http\Controllers\Administrasi\KwintansiController;
use App\Http\Controllers\Administrasi\InvoiceController;
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

    // Route Recap Sales
    Route::get('/recap-sales', [RecapSalesController::class, 'index'])->name('recap-sales.index');
    Route::post('/recap-sales', [RecapSalesController::class, 'store'])->name('recap-sales.store');
    Route::put('/recap-sales/{id_sales_recap}', [RecapSalesController::class, 'update'])->name('recap-sales.update');
    Route::patch('/recap-sales/{id_sales_recap}/status', [RecapSalesController::class, 'updateStatus'])->name('recap-sales.updateStatus');
    Route::delete('/recap-sales/destroy-selected', [RecapSalesController::class, 'destroySelected'])->name('recap-sales.destroySelected');

    // Export routes
    Route::get('/recap-sales/export/excel', [RecapSalesController::class, 'exportExcel'])->name('recap-sales.export.excel');
    Route::get('/recap-sales/export/pdf', [RecapSalesController::class, 'exportPdf'])->name('recap-sales.export.pdf');

    // Route Recap Expense
    Route::get('/recap-expense', [RecapExpenseController::class, 'index'])->name('recap-expense.index');
    Route::post('/recap-expense', [RecapExpenseController::class, 'store'])->name('recap-expense.store');
    Route::put('/recap-expense/{id}', [RecapExpenseController::class, 'update'])->name('recap-expense.update');
    Route::delete('/recap-expense/destroy-selected', [RecapExpenseController::class, 'destroySelected'])->name('recap-expense.destroySelected');

    // Recap Expense Export routes
    Route::get('/recap-expense/export/excel', [RecapExpenseController::class, 'exportExcel'])->name('recap-expense.export.excel');
    Route::get('/recap-expense/export/pdf', [RecapExpenseController::class, 'exportPdf'])->name('recap-expense.export.pdf');

    // Route Transaction Category
    Route::get('/transaction-category', [TransactionCategoryController::class, 'index'])->name('transaction-category.index');
    Route::post('/transaction-category', [TransactionCategoryController::class, 'store'])->name('transaction-category.store');
    Route::put('/transaction-category/{id}', [TransactionCategoryController::class, 'update'])->name('transaction-category.update');
    Route::patch('/transaction-category/{id}/toggle-status', [TransactionCategoryController::class, 'toggleStatus'])->name('transaction-category.toggleStatus');
    Route::delete('/transaction-category/destroy-selected', [TransactionCategoryController::class, 'destroySelected'])->name('transaction-category.destroySelected');

    // ============================================
    // Laporan Routes
    // ============================================

    // Route Laporan Rekap Penjualan
    Route::get('/report/sales', [SalesReportController::class, 'index'])->name('report.sales');

    // Route Laporan Rekap Pengeluaran
    Route::get('/report/expense', [ExpenseReportController::class, 'index'])->name('report.expense');

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

    // Route Cash Out Proof (Bukti Kas Keluar)
    Route::get('/cash-out-proof', [CashOutProofController::class, 'index'])->name('cash-out-proof.index');
    Route::post('/cash-out-proof', [CashOutProofController::class, 'store'])->name('cash-out-proof.store');
    Route::put('/cash-out-proof/{cashOutProof}', [CashOutProofController::class, 'update'])->name('cash-out-proof.update');
    Route::delete('/cash-out-proof/destroy-selected', [CashOutProofController::class, 'destroySelected'])->name('cash-out-proof.destroySelected');

    // Route Cash Out Proof - Export PDF
    Route::get('/cash-out-proof/export/pdf', [CashOutProofController::class, 'exportPdfAll'])->name('cash-out-proof.export.pdf');
    Route::post('/cash-out-proof/export/pdf-selected', [CashOutProofController::class, 'exportPdfSelected'])->name('cash-out-proof.export.pdf.selected');

    // Route Kwintansi
    Route::get('/kwintansi', [KwintansiController::class, 'index'])->name('kwintansi.index');
    Route::post('/kwintansi', [KwintansiController::class, 'store'])->name('kwintansi.store');
    Route::put('/kwintansi/{kwintansi}', [KwintansiController::class, 'update'])->name('kwintansi.update');
    Route::delete('/kwintansi/destroy-selected', [KwintansiController::class, 'destroySelected'])->name('kwintansi.destroySelected');

    // Route Kwintansi - Export PDF
    Route::get('/kwintansi/export/pdf', [KwintansiController::class, 'exportPdfAll'])->name('kwintansi.export.pdf');
    Route::post('/kwintansi/export/pdf-selected', [KwintansiController::class, 'exportPdfSelected'])->name('kwintansi.export.pdf.selected');

    // Route Invoice Administrasi
    Route::get('/invoice-administrasi', [InvoiceController::class, 'index'])->name('invoice.administrasi.index');
    Route::post('/invoice-administrasi', [InvoiceController::class, 'store'])->name('invoice.administrasi.store');
    Route::put('/invoice-administrasi/{invoice}', [InvoiceController::class, 'update'])->name('invoice.administrasi.update')->where('invoice', '.*');
    Route::delete('/invoice-administrasi/destroy-selected', [InvoiceController::class, 'destroySelected'])->name('invoice.administrasi.destroySelected');

    // Route Invoice Administrasi - Export PDF
    Route::get('/invoice-administrasi/export/pdf', [InvoiceController::class, 'exportPdfAll'])->name('invoice.administrasi.export.pdf');
    Route::post('/invoice-administrasi/export/pdf-selected', [InvoiceController::class, 'exportPdfSelected'])->name('invoice.administrasi.export.pdf.selected');

});