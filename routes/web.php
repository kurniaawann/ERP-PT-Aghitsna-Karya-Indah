<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\Inventory\ItemStockInController;
use App\Http\Controllers\Inventory\ItemStockOutController;
use App\Http\Controllers\Inventory\ItemReturnController;
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
use App\Http\Controllers\Sdm\KasbonController;
use App\Http\Controllers\Finance\ReimburseController;
use App\Http\Controllers\Administrasi\DocumentReceiptController;
use App\Http\Controllers\Administrasi\CashOutProofController;
use App\Http\Controllers\Administrasi\KwintansiController;
use App\Http\Controllers\Administrasi\InvoiceController;
use App\Http\Controllers\Administrasi\AluminiumQuotationController;
use App\Http\Controllers\Administrasi\ProjectQuotationController;
use App\Http\Controllers\Administrasi\RABController;
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

    // Route Stock In (Barang Masuk)
    Route::get('/stock-in', [ItemStockInController::class, 'index'])->name('stock-in.index');
    Route::post('/stock-in', [ItemStockInController::class, 'store'])->name('stock-in.store');
    Route::put('/stock-in/{id_stock_in}', [ItemStockInController::class, 'update'])->name('stock-in.update');
    Route::delete('/stock-in/{id_stock_in}', [ItemStockInController::class, 'destroy'])->name('stock-in.destroy');
    Route::get('/stock-in/export/pdf', [ItemStockInController::class, 'exportPdf'])->name('stock-in.export.pdf');
    Route::get('/stock-in/export/excel', [ItemStockInController::class, 'exportExcel'])->name('stock-in.export.excel');

    // Route Stock Out (Barang Keluar)
    Route::get('/stock-out', [ItemStockOutController::class, 'index'])->name('stock-out.index');
    Route::post('/stock-out', [ItemStockOutController::class, 'store'])->name('stock-out.store');
    Route::put('/stock-out/{id_stock_out}', [ItemStockOutController::class, 'update'])->name('stock-out.update');
    Route::delete('/stock-out/{id_stock_out}', [ItemStockOutController::class, 'destroy'])->name('stock-out.destroy');
    Route::get('/stock-out/export/pdf', [ItemStockOutController::class, 'exportPdf'])->name('stock-out.export.pdf');
    Route::get('/stock-out/export/excel', [ItemStockOutController::class, 'exportExcel'])->name('stock-out.export.excel');

    // Route Item Return (Return Barang)
    Route::get('/item-return', [ItemReturnController::class, 'index'])->name('item-return.index');
    Route::post('/item-return', [ItemReturnController::class, 'store'])->name('item-return.store');
    Route::put('/item-return/{id_return}', [ItemReturnController::class, 'update'])->name('item-return.update');
    Route::delete('/item-return/{id_return}', [ItemReturnController::class, 'destroy'])->name('item-return.destroy');
    Route::get('/item-return/export/pdf', [ItemReturnController::class, 'exportPdf'])->name('item-return.export.pdf');
    Route::get('/item-return/export/excel', [ItemReturnController::class, 'exportExcel'])->name('item-return.export.excel');

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

    // Route Kasbon (Cash Advance)
    Route::get('/kasbon', [KasbonController::class, 'index'])->name('kasbon.index');
    Route::post('/kasbon', [KasbonController::class, 'store'])->name('kasbon.store');
    Route::put('/kasbon/{kasbonCode}', [KasbonController::class, 'update'])->name('kasbon.update');
    Route::delete('/kasbon/destroy-selected', [KasbonController::class, 'destroySelected'])->name('kasbon.destroySelected');
    Route::post('/kasbon/get-total', [KasbonController::class, 'getTotalForPeriod'])->name('kasbon.get-total');
    Route::post('/kasbon/check-max', [KasbonController::class, 'checkMaxKasbon'])->name('kasbon.check-max');

    // Route Division
    Route::get('/division', [\App\Http\Controllers\Sdm\DivisionController::class, 'index'])->name('division.index');
    Route::post('/division', [\App\Http\Controllers\Sdm\DivisionController::class, 'store'])->name('division.store');
    Route::put('/division/{division}', [\App\Http\Controllers\Sdm\DivisionController::class, 'update'])->name('division.update');
    Route::delete('/division', [\App\Http\Controllers\Sdm\DivisionController::class, 'destroy'])->name('division.destroy');

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

    // ─── Penawaran Aluminium (Aluminium Quotation) ──────────────────────────────
    Route::get('/aluminium-quotation', [AluminiumQuotationController::class, 'index'])->name('aluminium-quotation.index');
    Route::get('/aluminium-quotation/next-number', [AluminiumQuotationController::class, 'getNextQuotationNumber'])->name('aluminium-quotation.getNextNumber');
    Route::post('/aluminium-quotation', [AluminiumQuotationController::class, 'store'])->name('aluminium-quotation.store');
    Route::delete('/aluminium-quotation/destroy-selected', [AluminiumQuotationController::class, 'destroySelected'])->name('aluminium-quotation.destroySelected');
    Route::get('/aluminium-quotation/{quotation_number}/print/pdf', [AluminiumQuotationController::class, 'printPdf'])->name('aluminium-quotation.print.pdf')->where('quotation_number', '.*');
    Route::get('/aluminium-quotation/{quotation_number}/print/excel', [AluminiumQuotationController::class, 'printExcel'])->name('aluminium-quotation.print.excel')->where('quotation_number', '.*');
    Route::post('/aluminium-quotation/export/pdf-selected', [AluminiumQuotationController::class, 'exportPdfSelected'])->name('aluminium-quotation.export.pdf.selected');
    Route::get('/aluminium-quotation/{quotation_number}', [AluminiumQuotationController::class, 'show'])->name('aluminium-quotation.show')->where('quotation_number', '.*');
    Route::put('/aluminium-quotation/{aluminium_quotation}', [AluminiumQuotationController::class, 'update'])->name('aluminium-quotation.update')->where('aluminium_quotation', '.*');

    // ─── Penawaran Proyek (Project Quotation) ───────────────────────────────────
    Route::get('/project-quotation', [ProjectQuotationController::class, 'index'])->name('project-quotation.index');
    Route::get('/project-quotation/next-number', [ProjectQuotationController::class, 'getNextQuotationNumber'])->name('project-quotation.getNextNumber');
    Route::post('/project-quotation', [ProjectQuotationController::class, 'store'])->name('project-quotation.store');
    Route::delete('/project-quotation/destroy-selected', [ProjectQuotationController::class, 'destroySelected'])->name('project-quotation.destroySelected');
    Route::post('/project-quotation/export/pdf-selected', [ProjectQuotationController::class, 'exportPdfSelected'])->name('project-quotation.export.pdf.selected');

    // Specific routes MUST come before generic {quotation_number} routes
    Route::get('/project-quotation/{quotation_number}/print/pdf', [ProjectQuotationController::class, 'printPdfSingle'])->name('project-quotation.print.pdf')->where('quotation_number', '[^/]+/[^/]+/[^/]+/[0-9]+');
    Route::get('/project-quotation/{quotation_number}/print/excel', [ProjectQuotationController::class, 'printExcelSingle'])->name('project-quotation.print.excel')->where('quotation_number', '[^/]+/[^/]+/[^/]+/[0-9]+');

    // Generic routes come last
    Route::get('/project-quotation/{quotation_number}', [ProjectQuotationController::class, 'show'])->name('project-quotation.show')->where('quotation_number', '[^/]+/[^/]+/[^/]+/[0-9]+');
    Route::put('/project-quotation/{quotation_number}', [ProjectQuotationController::class, 'update'])->name('project-quotation.update')->where('quotation_number', '[^/]+/[^/]+/[^/]+/[0-9]+');

    // ─── RAB (Rancangan Anggaran Biaya) ─────────────────────────────────────────
    Route::get('/rab', [RABController::class, 'index'])->name('rab.index');
    Route::get('/rab/next-number', [RABController::class, 'getNextRABNumber'])->name('rab.getNextNumber');
    Route::post('/rab', [RABController::class, 'store'])->name('rab.store');
    Route::delete('/rab/destroy', [RABController::class, 'destroy'])->name('rab.destroy');
    Route::get('/rab/{rab_number}/export-pdf', [RABController::class, 'exportPDF'])->name('rab.export-pdf')->where('rab_number', '.*');
    Route::get('/rab/{rab_number}', [RABController::class, 'show'])->name('rab.show')->where('rab_number', '.*');
    Route::get('/rab/{rab_number}/edit', [RABController::class, 'edit'])->name('rab.edit')->where('rab_number', '.*');
    Route::put('/rab/{rab_number}', [RABController::class, 'update'])->name('rab.update')->where('rab_number', '.*');

});