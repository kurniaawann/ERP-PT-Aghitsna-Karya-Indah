<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreProjectQuotationRequest;
use App\Http\Requests\Administrasi\UpdateProjectQuotationRequest;
use App\Models\Administrasi\ProjectQuotation;
use App\Models\Finance\PaymentAccount;
use App\Exports\Administrasi\ProjectQuotationExport;
use App\Services\Administrasi\ProjectQuotationService;
use App\Traits\HasBulkActions;
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
    use HasBulkActions;

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
        $quotations = $this->service->getPaginatedSearch($search);
        $paymentAccounts = $this->service->getActivePaymentAccounts();

        return view('pages.administrasi.project-quotation', compact('quotations', 'paymentAccounts', 'search'));
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
            'recipient' => $quotation->recipient,
                'project_description' => $quotation->project_description,
            'total_amount' => $quotation->total_amount,
            'amount_in_words' => $quotation->amount_in_words,
            'selected_payment_accounts' => is_string($quotation->selected_payment_accounts)
                ? json_decode($quotation->selected_payment_accounts, true)
                : $quotation->selected_payment_accounts,
            'signed_by' => $quotation->signed_by,
            'division' => $quotation->division,
            'items' => $quotation->items->map(function ($item) {
                return [
                    'order_number' => $item->order_number,
                    'description' => $item->description,
                    'volume' => $item->volume,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ];
            })->toArray(),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    /**
     * Menyimpan penawaran baru.
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

            $items = $quotation->items()->orderBy('order_number')->get();

            $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf', compact('quotation'))
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
            new ProjectQuotationExport($quotationNumber),
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

            $quotation->items = $quotation->items()->orderBy('order_number')->get();
        }

        // Single PDF view or bulk view
        if (count($quotationNumbers) === 1) {
            $quotation = $quotations->first();

            $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf', compact('quotation'))
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
            $pdf = Pdf::loadView('exports.administrasi.project-quotation-pdf', compact('quotations'))
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
                new ProjectQuotationExport($quotationNumbers[0]),
                "Penawaran_Proyek_{$safeFileName}_{$date}.xlsx"
            );
        } else {
            // For multiple, create a multi-sheet Excel file
            return Excel::download(
                new \App\Exports\Administrasi\ProjectQuotationMultiExport($quotationNumbers),
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
