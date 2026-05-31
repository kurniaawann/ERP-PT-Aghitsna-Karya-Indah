<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\Inventory\ItemStockInController;
use App\Http\Controllers\Inventory\ItemStockOutController;
use App\Http\Controllers\Inventory\ItemReturnController;
use App\Http\Controllers\Inventory\StockReportController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Finance\AlumuniumInvoiceController;
use App\Http\Controllers\Finance\ItemInvoiceController;
use App\Http\Controllers\Finance\RecapAlumuniumController;
use App\Http\Controllers\Finance\RecapProyekController;
use App\Http\Controllers\Finance\ProyekInvoiceController;
use App\Http\Controllers\Finance\PaymentProofController;
use App\Http\Controllers\Finance\PurchaseInvoiceController;
use App\Http\Controllers\Finance\PaymentAccountController;
use App\Http\Controllers\Finance\RecapSalesController;
use App\Http\Controllers\Finance\RecapExpenseController;
use App\Http\Controllers\Report\TransactionCategoryController;
use App\Http\Controllers\Report\SalesReportController;
use App\Http\Controllers\Report\ExpenseReportController;
use App\Http\Controllers\Notification\SalaryReminderController;
use App\Http\Controllers\Notification\InvoiceProyekReminderController;
use App\Http\Controllers\Sdm\EmployeeController;
use App\Http\Controllers\Sdm\AttendanceController;
use App\Http\Controllers\Sdm\OvertimeController;
use App\Http\Controllers\Sdm\PayrollController;
use App\Http\Controllers\Sdm\KasbonController;
use App\Http\Controllers\Finance\ReimburseController;
use App\Http\Controllers\Administrasi\DocumentReceiptController;
use App\Http\Controllers\Administrasi\CashOutProofController;
use App\Http\Controllers\Administrasi\KwintansiController;
use App\Http\Controllers\Administrasi\NotaController;
use App\Http\Controllers\Administrasi\DeliveryNoteController;
use App\Http\Controllers\Administrasi\AluminiumQuotationController;
use App\Http\Controllers\Administrasi\ProjectQuotationController;
use App\Http\Controllers\Administrasi\RABController;
use App\Http\Controllers\UserManagement\UserController;
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


// Route authentication (untuk user yang belum login)
Route::middleware('guest')->group(function () {
    // Login routes
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Forgot password routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    // Reset password routes
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Logout route (untuk user yang sudah login)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

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
    Route::delete('/stock-ins', [ItemStockInController::class, 'destroySelected'])->name('stock-ins.destroySelected');
    Route::get('/stock-in/export/pdf', [ItemStockInController::class, 'exportPdf'])->name('stock-in.export.pdf');
    Route::get('/stock-in/export/excel', [ItemStockInController::class, 'exportExcel'])->name('stock-in.export.excel');

    // Route Stock Out (Barang Keluar) - Read Only
    Route::get('/stock-out', [ItemStockOutController::class, 'index'])->name('stock-out.index');
    Route::get('/stock-out/export/pdf', [ItemStockOutController::class, 'exportPdf'])->name('stock-out.export.pdf');
    Route::get('/stock-out/export/excel', [ItemStockOutController::class, 'exportExcel'])->name('stock-out.export.excel');

    // Route Item Return (Return Barang)
    Route::get('/item-return', [ItemReturnController::class, 'index'])->name('item-return.index');
    Route::post('/item-return', [ItemReturnController::class, 'store'])->name('item-return.store');
    Route::put('/item-return/{id_return}', [ItemReturnController::class, 'update'])->name('item-return.update');
    Route::delete('/item-return/bulk-delete', [ItemReturnController::class, 'bulkDelete'])->name('item-return.bulk-delete');
    Route::delete('/item-return/{id_return}', [ItemReturnController::class, 'destroy'])->name('item-return.destroy');
    Route::get('/item-return/export/pdf', [ItemReturnController::class, 'exportPdf'])->name('item-return.export.pdf');
    Route::get('/item-return/export/excel', [ItemReturnController::class, 'exportExcel'])->name('item-return.export.excel');

    // Route Stock Report (Laporan Stok)
    Route::get('/stock-report', [StockReportController::class, 'index'])->name('stock-report.index');
    Route::get('/stock-report/items-dropdown', [StockReportController::class, 'itemsDropdown'])->name('stock-report.items-dropdown');

    // Route Item Invoice
    Route::get('/item-invoice', [ItemInvoiceController::class, 'index'])->name('item-invoice.index');
    Route::get('/item-invoice/next-number', [ItemInvoiceController::class, 'getNextInvoiceNumber'])->name('item-invoice.getNextNumber');
    Route::post('/item-invoice', [ItemInvoiceController::class, 'store'])->name('item-invoice.store');
    Route::get('/item-invoice/{item_invoice}/edit', [ItemInvoiceController::class, 'edit'])->name('item-invoice.edit')->where('item_invoice', '.*');
    Route::put('/item-invoice/{item_invoice}', [ItemInvoiceController::class, 'update'])->name('item-invoice.update')->where('item_invoice', '.*');
    Route::delete('/item-invoice/destroy-selected', [ItemInvoiceController::class, 'destroySelected'])->name('item-invoice.destroySelected');
    Route::get('/item-invoice/export/excel', [ItemInvoiceController::class, 'exportExcel'])->name('item-invoice.export.excel');
    Route::get('/item-invoice/export/pdf', [ItemInvoiceController::class, 'exportPdf'])->name('item-invoice.export.pdf');

    // Item Invoice Print Routes
    Route::get('/item-invoice/{invoice_number}/print/pdf', [ItemInvoiceController::class, 'printPdf'])->name('item-invoice.print.pdf')->where('invoice_number', '.*');
    Route::get('/item-invoice/{invoice_number}/print/excel', [ItemInvoiceController::class, 'printExcel'])->name('item-invoice.print.excel')->where('invoice_number', '.*');

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

    // Route Bukti Pembayaran Invoice
    Route::get('/payment-proofs', [PaymentProofController::class, 'index'])->name('payment-proofs.index');
    Route::post('/payment-proofs', [PaymentProofController::class, 'store'])->name('payment-proofs.store');
    Route::put('/payment-proofs/{payment_proof}', [PaymentProofController::class, 'update'])->name('payment-proofs.update');
    Route::delete('/payment-proofs/{payment_proof}', [PaymentProofController::class, 'destroy'])->name('payment-proofs.destroy');

    // Route Purchase Invoice
    Route::get('/purchase-invoice', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoice.index');
    Route::post('/purchase-invoice', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoice.store');
    Route::delete('/purchase-invoice/destroy-selected', [PurchaseInvoiceController::class, 'destroySelected'])->name('purchase-invoice.destroy-selected');
    Route::get('/purchase-invoice/export/excel', [PurchaseInvoiceController::class, 'exportExcel'])->name('purchase-invoice.export-excel');
    Route::get('/purchase-invoice/export/pdf', [PurchaseInvoiceController::class, 'exportPdf'])->name('purchase-invoice.export-pdf');
    Route::get('/purchase-invoice/{id}/print/pdf', [PurchaseInvoiceController::class, 'printPdf'])->name('purchase-invoice.pdf');
    Route::get('/purchase-invoice/{purchase_invoice}/edit', [PurchaseInvoiceController::class, 'edit'])->name('purchase-invoice.edit');
    Route::put('/purchase-invoice/{purchase_invoice}', [PurchaseInvoiceController::class, 'update'])->name('purchase-invoice.update');
    Route::delete('/purchase-invoice/{purchase_invoice}', [PurchaseInvoiceController::class, 'destroy'])->name('purchase-invoice.destroy');

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

    // Route Recap Alumunium
    Route::get('/recap-alumunium', [RecapAlumuniumController::class, 'index'])->name('recap-alumunium.index');
    Route::get('/recap-alumunium/export/excel', [RecapAlumuniumController::class, 'exportExcel'])->name('recap-alumunium.export.excel');
    Route::get('/recap-alumunium/export/pdf', [RecapAlumuniumController::class, 'exportPdf'])->name('recap-alumunium.export.pdf');

    // Route Recap Proyek
    Route::get('/recap-proyek', [RecapProyekController::class, 'index'])->name('recap-proyek.index');
    Route::get('/recap-proyek/export/excel', [RecapProyekController::class, 'exportExcel'])->name('recap-proyek.export.excel');
    Route::get('/recap-proyek/export/pdf', [RecapProyekController::class, 'exportPdf'])->name('recap-proyek.export.pdf');

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
    // Notification Routes (Notifikasi & Reminder)
    // ============================================

    // Route Reminder Gaji Karyawan
    Route::get('/notification/salary-reminder', [SalaryReminderController::class, 'index'])->name('notification.salary-reminder');

    // Route Reminder Jatuh Tempo Invoice Proyek
    Route::get('/notification/invoice-proyek-reminder', [InvoiceProyekReminderController::class, 'index'])->name('notification.invoice-proyek-reminder');
    Route::put('/notification/invoice-proyek-reminder/{id}/status', [InvoiceProyekReminderController::class, 'updateStatus'])->name('notification.invoice-proyek-reminder.update-status');
    Route::post('/notification/invoice-proyek-reminder/bulk-update-status', [InvoiceProyekReminderController::class, 'bulkUpdateStatus'])->name('notification.invoice-proyek-reminder.bulk-update-status');

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
    Route::put('/payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
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

    // Route Nota Administrasi
    Route::get('/nota-administrasi', [NotaController::class, 'index'])->name('nota.administrasi.index');
    Route::post('/nota-administrasi', [NotaController::class, 'store'])->name('nota.administrasi.store');
    Route::put('/nota-administrasi/{nota}', [NotaController::class, 'update'])->name('nota.administrasi.update')->where('nota', '.*');
    Route::delete('/nota-administrasi/destroy-selected', [NotaController::class, 'destroySelected'])->name('nota.administrasi.destroySelected');

    // Route Nota Administrasi - Export PDF
    Route::get('/nota-administrasi/export/pdf', [NotaController::class, 'exportPdfAll'])->name('nota.administrasi.export.pdf');
    Route::post('/nota-administrasi/export/pdf-selected', [NotaController::class, 'exportPdfSelected'])->name('nota.administrasi.export.pdf.selected');

    // Route Delivery Note (Surat Jalan)
    Route::get('/delivery-note', [DeliveryNoteController::class, 'index'])->name('delivery-note.administrasi.index');
    Route::post('/delivery-note', [DeliveryNoteController::class, 'store'])->name('delivery-note.administrasi.store');
    Route::put('/delivery-note/{deliveryNote}', [DeliveryNoteController::class, 'update'])->name('delivery-note.administrasi.update')->where('deliveryNote', '.*');
    Route::delete('/delivery-note/destroy-selected', [DeliveryNoteController::class, 'destroySelected'])->name('delivery-note.administrasi.destroySelected');
    Route::patch('/delivery-note/{deliveryNote}/status', [DeliveryNoteController::class, 'updateStatus'])->name('delivery-note.administrasi.updateStatus')->where('deliveryNote', '.*');

    // Route Delivery Note - Export PDF
    Route::get('/delivery-note/export/pdf', [DeliveryNoteController::class, 'exportPdfAll'])->name('delivery-note.administrasi.export.pdf');
    Route::post('/delivery-note/export/pdf-selected', [DeliveryNoteController::class, 'exportPdfSelected'])->name('delivery-note.administrasi.export.pdf.selected');

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

    // ============================================
    // User Management Routes (Super Admin only)
    // ============================================
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/user-management', [UserController::class, 'index'])->name('user-management.index');
        Route::post('/user-management', [UserController::class, 'store'])->name('user-management.store');
        Route::delete('/user-management/destroy-selected', [UserController::class, 'destroy'])->name('user-management.destroy');
        Route::put('/user-management/{user}', [UserController::class, 'update'])->name('user-management.update');
    });

    // ─── RAB (Rancangan Anggaran Biaya) ─────────────────────────────────────────
    Route::get('/rab', [RABController::class, 'index'])->name('rab.index');
    Route::get('/rab/next-number', [RABController::class, 'getNextRABNumber'])->name('rab.getNextNumber');
    Route::post('/rab', [RABController::class, 'store'])->name('rab.store');
    Route::delete('/rab/destroy', [RABController::class, 'destroy'])->name('rab.destroy');
    Route::get('/rab/{rab_number}/export-excel', [RABController::class, 'exportExcel'])->name('rab.export-excel')->where('rab_number', '.*');
    Route::get('/rab/{rab_number}/export-pdf', [RABController::class, 'exportPDF'])->name('rab.export-pdf')->where('rab_number', '.*');
    Route::get('/rab/{rab_number}', [RABController::class, 'show'])->name('rab.show')->where('rab_number', '.*');
    Route::get('/rab/{rab_number}/edit', [RABController::class, 'edit'])->name('rab.edit')->where('rab_number', '.*');
    Route::put('/rab/{rab_number}', [RABController::class, 'update'])->name('rab.update')->where('rab_number', '.*');

});