<?php

namespace App\Http\Controllers\Finance;

use App\Exports\Finance\SemenInvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\Finance\InvoiceSemen;
use App\Services\Finance\SemenInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller modul Invoice Semen (Finance).
 *
 * Invoice Semen kini dibuat otomatis dari DO Semen (alur superadmin di
 * modul DO Semen). Controller ini hanya menyisakan aksi cetak PDF/Excel
 * untuk invoice yang sudah dibuat. Pembuatan/ubah/hapus manual tidak
 * lagi tersedia (tab "Invoice Semen" telah dihapus).
 */
class SemenInvoiceController extends Controller
{
    public function __construct(
        private readonly SemenInvoiceService $service
    ) {}

    /**
     * Mencetak Invoice Semen sebagai PDF.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printPdf(string $invoiceNumber)
    {
        $invoice = InvoiceSemen::where('invoice_number', $invoiceNumber)->firstOrFail();

        $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

        $pdf = Pdf::loadView('exports.finance.semen-invoice-pdf', compact('invoice', 'isSuperAdmin'));
        $pdf->setPaper('a4', 'portrait');

        $safeFileName = str_replace(['/', '\\'], '-', $invoice->invoice_number);
        $date = date('Y-m-d');

        return $pdf->download("Invoice_Semen_{$safeFileName}_{$date}.pdf");
    }

    /**
     * Mencetak Invoice Semen sebagai Excel.
     *
     * @param  string  $invoiceNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcel(string $invoiceNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $invoiceNumber);
        $date = date('Y-m-d');

        return Excel::download(new SemenInvoiceExport($invoiceNumber), "Invoice_Semen_{$safeFileName}_{$date}.xlsx");
    }
}