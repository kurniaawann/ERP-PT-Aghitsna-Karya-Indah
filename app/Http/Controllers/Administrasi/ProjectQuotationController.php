<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreProjectQuotationRequest;
use App\Http\Requests\Administrasi\UpdateProjectQuotationRequest;
use App\Models\Administrasi\ProjectQuotation;
use App\Models\Finance\PaymentAccount;
use App\Models\Sdm\Executive;
use App\Models\Sdm\Division;
use App\Services\Administrasi\ProjectQuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk modul Penawaran Proyek (Project Quotation).
 *
 * Controller ini menggunakan Service layer untuk business logic
 * dan FormRequest untuk validasi data.
 */
class ProjectQuotationController extends Controller
{
    /**
     * @var ProjectQuotationService
     */
    protected $service;

    /**
     * @param  ProjectQuotationService  $service
     */
    public function __construct(ProjectQuotationService $service)
    {
        $this->service = $service;
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    /**
     * Menampilkan daftar penawaran proyek dengan paginasi dan pencarian.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;
        $quotations = $this->service->getPaginatedSearch($search, $month, $year);
        $paymentAccounts = $this->service->getActivePaymentAccounts();
        $executives = Executive::where('created_by', auth()->id())->orderBy('name')->get();
        $divisions = Division::where('created_by', auth()->id())->orderBy('name')->get();

        return view('pages.administrasi.project-quotation', compact('quotations', 'paymentAccounts', 'search', 'executives', 'divisions'));
    }

    // ─── Get Next Number (AJAX) ───────────────────────────────────────────────

    /**
     * Mendapatkan nomor penawaran berikutnya via AJAX.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextQuotationNumber()
    {
        return response()->json([
            'quotation_number' => $this->service->generateQuotationNumber(),
        ]);
    }

    // ─── Show (Get for Edit) ──────────────────────────────────────────────────

    /**
     * Mendapatkan data penawaran berdasarkan nomor (untuk edit modal).
     *
     * @param  string  $quotationNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $quotationNumber)
    {
        $quotation = $this->service->findByNumber($quotationNumber);

        if (!$quotation) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'quotation_number' => $quotation->quotation_number,
            'date' => $quotation->date,
            'subject' => $quotation->subject,
            'attachment' => $quotation->attachment,
            'recipient' => $quotation->recipient,
            'project_description' => $quotation->project_description,
            'location' => $quotation->location,
            'total_amount' => $quotation->total_amount,
            'discount_type' => $quotation->discount_type,
            'discount_value' => $quotation->discount_value,
            'total_after_discount' => $quotation->total_after_discount,
            'amount_in_words' => $quotation->amount_in_words,
            'selected_payment_accounts' => is_string($quotation->selected_payment_accounts)
                ? json_decode($quotation->selected_payment_accounts, true)
                : $quotation->selected_payment_accounts,
            'signed_by_id' => $quotation->signed_by_id,
            'division_id' => $quotation->division_id,
            'items' => $quotation->items ?? [],
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    /**
     * Menyimpan penawaran baru.
     *
     * Catatan: penawaran TIDAK otomatis membuat invoice. Invoice dibuat
     * lewat aksi eksplisit "Buat Invoice" (createInvoiceFromQuotation).
     *
     * @param  StoreProjectQuotationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreProjectQuotationRequest $request)
    {
        $quotation = $this->service->create($request->validated());

        return redirect()->route('project-quotation.index')
            ->with('success', "Penawaran {$quotation->quotation_number} berhasil ditambahkan!");
    }

    /**
     * Membuat Invoice Proyek (snapshot) dari penawaran yang diterima.
     *
     * @param  string  $quotationNumber
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createInvoiceFromQuotation(string $quotationNumber)
    {
        $quotation = $this->service->findByNumber($quotationNumber);

        if (!$quotation) {
            return back()->with('error', 'Data tidak ditemukan!');
        }

        if ($quotation->invoices()->exists()) {
            return back()->with('error', "Invoice untuk penawaran {$quotation->quotation_number} sudah pernah dibuat.");
        }

        $invoice = $this->service->createInvoiceFromQuotation($quotation);

        return redirect()->route('proyek-invoice.index')
            ->with('success', "Invoice {$invoice->invoice_number} berhasil dibuat dari penawaran {$quotation->quotation_number}. Silakan lengkapi PPN, DP, dan rekening pembayaran pada modul Finance.");
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    /**
     * Memperbarui penawaran yang sudah ada.
     *
     * @param  UpdateProjectQuotationRequest  $request
     * @param  string                          $quotationNumber
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProjectQuotationRequest $request, string $quotationNumber)
    {
        $quotation = ProjectQuotation::findOrFail($quotationNumber);
        $this->service->update($quotation, $request->validated());

        return redirect()->route('project-quotation.index')
            ->with('success', 'Penawaran berhasil diperbarui!');
    }

    // ─── Destroy Selected ────────────────────────────────────────────────────

    /**
     * Menghapus beberapa penawaran sekaligus.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $count = $this->service->deleteByIds($ids);

        return redirect()->route('project-quotation.index')
            ->with('success', "{$count} data terpilih berhasil dihapus.");
    }

    // ─── Print PDF (Single from GET route) ───────────────────────────────────

    /**
     * Generate dan download PDF untuk satu penawaran.
     *
     * @param  string  $quotationNumber
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function printPdfSingle(string $quotationNumber)
    {
        try {
            $quotation = $this->service->findByNumber($quotationNumber);

            if (!$quotation) {
                return back()->with('error', 'Data tidak ditemukan!');
            }

            $selectedAccountIds = is_string($quotation->selected_payment_accounts)
                ? json_decode($quotation->selected_payment_accounts, true)
                : ($quotation->selected_payment_accounts ?? []);

            if (!empty($selectedAccountIds)) {
                $paymentAccounts = PaymentAccount::whereIn('id', $selectedAccountIds)
                    ->orderBy('id')
                    ->get();
            } else {
                $paymentAccounts = PaymentAccount::where('is_active', true)->get();
            }

            $items = $quotation->items ?? [];

            $pdf = Pdf::loadView($this->service->getPdfView(), compact('quotation'))
                ->setPaper('a4', 'portrait');

            $safeNumber = str_replace(['/', '\\'], '-', $quotation->quotation_number);
            $date = date('Y-m-d');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, "Penawaran_Proyek_{$safeNumber}_{$date}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal generate PDF: ' . $e->getMessage());
        }
    }

    // ─── Print Excel (Single from GET route) ─────────────────────────────────

    /**
     * Generate dan download Excel untuk satu penawaran.
     *
     * @param  string  $quotationNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function printExcelSingle(string $quotationNumber)
    {
        $safeFileName = str_replace(['/', '\\'], '-', $quotationNumber);
        $date = date('Y-m-d');
        return Excel::download(
            $this->service->getExcelExport($quotationNumber),
            "Penawaran_Proyek_{$safeFileName}_{$date}.xlsx"
        );
    }

    // ─── Print PDF (Single or Multiple) ──────────────────────────────────────

    /**
     * Generate dan download PDF untuk satu atau beberapa penawaran.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function printPdf(Request $request)
    {
        $quotationNumbers = $request->input('quotation_numbers', []);

        if (empty($quotationNumbers)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $quotations = $this->service->getByIds($quotationNumbers);

        if ($quotations->isEmpty()) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Data tidak ditemukan!');
        }

        // Get payment accounts for each quotation
        foreach ($quotations as $quotation) {
            $selectedAccountIds = is_string($quotation->selected_payment_accounts)
                ? json_decode($quotation->selected_payment_accounts, true)
                : ($quotation->selected_payment_accounts ?? []);

            if (!empty($selectedAccountIds)) {
                $quotation->paymentAccounts = PaymentAccount::whereIn('id', $selectedAccountIds)
                    ->orderBy('id')
                    ->get();
            } else {
                $quotation->paymentAccounts = PaymentAccount::where('is_active', true)->get();
            }
        }

        // Single PDF view or bulk view
        if (count($quotationNumbers) === 1) {
            $quotation = $quotations->first();

            $pdf = Pdf::loadView($this->service->getPdfView(), compact('quotation'))
                ->setPaper('a4', 'portrait');

            $safeNumber = str_replace(['/', '\\'], '-', $quotation->quotation_number);
            $date = date('Y-m-d');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, "Penawaran_Proyek_{$safeNumber}_{$date}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);
        } else {
            // Multiple quotations - create separate pages
            $pdf = Pdf::loadView($this->service->getPdfView(), compact('quotations'))
                ->setPaper('a4', 'portrait');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'Penawaran_Proyek_' . date('Y-m-d') . '.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        }
    }

    // ─── Print Excel (Single or Multiple) ────────────────────────────────────

    /**
     * Generate dan download Excel untuk satu atau beberapa penawaran.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function printExcel(Request $request)
    {
        $quotationNumbers = $request->input('quotation_numbers', []);

        if (empty($quotationNumbers)) {
            return redirect()->route('project-quotation.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        if (count($quotationNumbers) === 1) {
            $safeFileName = str_replace(['/', '\\'], '-', $quotationNumbers[0]);
            $date = date('Y-m-d');
            return Excel::download(
                $this->service->getExcelExport($quotationNumbers[0]),
                "Penawaran_Proyek_{$safeFileName}_{$date}.xlsx"
            );
        } else {
            // For multiple, create a multi-sheet Excel file
            return Excel::download(
                $this->service->getExcelMultiExport($quotationNumbers),
                'Penawaran_Proyek_' . date('Y-m-d') . '.xlsx'
            );
        }
    }

    // ─── Export selected PDF ─────────────────────────────────────────────────

    /**
     * Export PDF untuk penawaran yang dipilih (delegate ke printPdf).
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportPdfSelected(Request $request)
    {
        return $this->printPdf($request);
    }
}
