<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreAluminiumQuotationRequest;
use App\Http\Requests\Administrasi\UpdateAluminiumQuotationRequest;
use App\Models\Administrasi\AluminiumQuotation;
use App\Services\Administrasi\AluminiumQuotationService;
use App\Models\Sdm\Executive;
use App\Models\Sdm\Division;
use App\Exports\Administrasi\AluminiumQuotationExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk mengelola Penawaran Aluminium (Aluminium Quotation).
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic didelegasikan ke AluminiumQuotationService.
 */
class AluminiumQuotationController extends Controller
{
    /**
     * @var AluminiumQuotationService Service layer untuk penawaran aluminium
     */
    protected AluminiumQuotationService $quotationService;

    /**
     * Konstruktor - inject AluminiumQuotationService.
     *
     * @param  AluminiumQuotationService  $quotationService  Service penawaran aluminium
     */
    public function __construct(AluminiumQuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    /**
     * Menampilkan daftar penawaran aluminium dengan pencarian dan paginasi.
     *
     * @param  Request  $request  Request HTTP
     * @return \Illuminate\View\View  Halaman daftar penawaran
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;
        $quotations = $this->quotationService->getPaginatedSearch($search, $month, $year);
        $paymentAccounts = $this->quotationService->getActivePaymentAccounts();
        $executives = Executive::where('created_by', auth()->id())->orderBy('name')->get();
        $divisions = Division::where('created_by', auth()->id())->orderBy('name')->get();

        return view('pages.administrasi.aluminium-quotation', compact('quotations', 'paymentAccounts', 'search', 'executives', 'divisions'));
    }

    /**
     * Mendapatkan nomor penawaran berikutnya via AJAX.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextQuotationNumber()
    {
        return response()->json([
            'quotation_number' => $this->quotationService->generateQuotationNumber(),
        ]);
    }

    /**
     * Menyimpan penawaran baru ke database.
     *
     * @param  StoreAluminiumQuotationRequest  $request  Request dengan data yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAluminiumQuotationRequest $request)
    {
        $quotation = $this->quotationService->create($request->validated());

        return redirect()->route('aluminium-quotation.index')
            ->with('success', "Penawaran {$quotation->quotation_number} berhasil ditambahkan! Invoice Alumunium otomatis dibuat.");
    }

    /**
     * Memperbarui data penawaran yang sudah ada.
     *
     * @param  UpdateAluminiumQuotationRequest  $request  Request dengan data yang sudah divalidasi
     * @param  string                             $aluminium_quotation  Nomor penawaran
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAluminiumQuotationRequest $request, string $aluminium_quotation)
    {
        $quotation = AluminiumQuotation::findOrFail($aluminium_quotation);

        $this->quotationService->update($quotation, $request->validated());

        return redirect()->route('aluminium-quotation.index')
            ->with('success', 'Penawaran berhasil diperbarui!');
    }

    /**
     * Membuat Invoice Alumunium (snapshot) dari penawaran yang diterima.
     *
     * Penawaran alumunium otomatis membuat invoice saat disimpan. Aksi ini
     * dipakai untuk membuat ulang invoice bila invoice tersebut terhapus.
     *
     * @param  string  $quotationNumber  Nomor penawaran
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createInvoiceFromQuotation(string $quotationNumber)
    {
        $quotation = $this->quotationService->findByNumber($quotationNumber);

        if (!$quotation) {
            return back()->with('error', 'Data tidak ditemukan!');
        }

        if ($quotation->invoices()->exists()) {
            return back()->with('error', "Invoice untuk penawaran {$quotation->quotation_number} sudah pernah dibuat.");
        }

        $invoice = $this->quotationService->createInvoiceFromQuotation($quotation);

        return redirect()->route('alumunium-invoice.index')
            ->with('success', "Invoice {$invoice->invoice_number} berhasil dibuat dari penawaran {$quotation->quotation_number}. Silakan lengkapi DP dan rekening pembayaran pada modul Finance.");
    }

    /**
     * Menghapus beberapa penawaran sekaligus (bulk delete).
     *
     * @param  Request  $request  Request HTTP dengan array nomor penawaran
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('aluminium-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $this->quotationService->deleteByIds($ids);

        $message = count($ids) . ' data terpilih berhasil dihapus.';
        return redirect()->route('aluminium-quotation.index')
            ->with('success', $message);
    }

    /**
     * Export satu penawaran ke PDF.
     *
     * @param  string  $quotationNumber  Nomor penawaran
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printPdf(string $quotationNumber)
    {
        $quotation = $this->quotationService->findByNumber($quotationNumber);

        if (!$quotation) {
            abort(404);
        }

        $pdf = Pdf::loadView('exports.administrasi.aluminium-quotation-pdf', compact('quotation'))
            ->setPaper('a4', 'portrait');

        $safeNumber = str_replace(['/', '\\'], '-', $quotationNumber);
        $date = date('Y-m-d');
        return $pdf->download("Penawaran_Aluminium_{$safeNumber}_{$date}.pdf");
    }

    /**
     * Export satu penawaran ke Excel.
     *
     * @param  string  $quotationNumber  Nomor penawaran
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcel(string $quotationNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $quotationNumber);
        $date = date('Y-m-d');

        return Excel::download(new AluminiumQuotationExport($quotationNumber), "Penawaran_Aluminium_{$safeFileName}_{$date}.xlsx");
    }

    /**
     * Export beberapa penawaran terpilih ke PDF.
     *
     * @param  Request  $request  Request HTTP dengan array nomor penawaran
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('aluminium-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $quotations = $this->quotationService->getByIds($ids);

        $pdf = Pdf::loadView('exports.administrasi.aluminium-quotation-pdf', compact('quotations'))
            ->setPaper('a4', 'portrait');

        if (count($ids) === 1) {
            $safeId = str_replace(['/', '\\'], '-', $ids[0]);
            $date = date('Y-m-d');
            $filename = "Penawaran_Aluminium_{$safeId}_{$date}.pdf";
        } else {
            $filename = 'Penawaran_Aluminium_' . date('Y-m-d') . '.pdf';
        }

        return $pdf->download($filename);
    }
}
