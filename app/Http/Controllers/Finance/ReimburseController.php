<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreReimburseRequest;
use App\Http\Requests\Finance\UpdateReimburseRequest;
use App\Models\Finance\Reimburse;
use App\Exports\Finance\ReimburseExport;
use App\Services\Finance\ReimburseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk modul Reimbursement.
 *
 * Menangani HTTP request untuk operasi CRUD, persetujuan,
 * penolakan, dan ekspor data reimbursement.
 *
 * Seluruh business logic didelegasikan ke ReimburseService.
 */
class ReimburseController extends Controller
{
    public function __construct(
        private ReimburseService $reimburseService
    ) {}

    /**
     * Menampilkan halaman index reimburse dengan filter & search.
     *
     * @param  \Illuminate\Http\Request $request  Request dengan parameter filter
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Tandai halaman Reimbursement sudah dibuka user (badge sidebar hilang).
        if ($user = $request->user()) {
            $user->forceFill(['reimburse_seen_at' => now()])->save();
        }

        $reimburses = $this->reimburseService
            ->buildFilteredQuery($request, 'created_at')
            ->with('nota:id_nota,penerima,penandatangan,created_by')
            ->paginate(15)
            ->appends($request->query());

        $search = $request->input('search');
        $status = $request->input('status');
        $month = $request->input('month');
        $year = $request->input('year');

        // "Ambil dari Nota" pada modal Tambah (Super Admin): nota tanpa reimburse
        $notaOptions = $request->user()?->isSuperAdmin()
            ? $this->reimburseService->getNotaOptionsForReimburse()
            : [];

        return view('pages.finance.reimburse', compact('reimburses', 'search', 'status', 'month', 'year', 'notaOptions'));
    }

    /**
     * Menyimpan data reimburse baru.
     *
     * @param  \App\Http\Requests\Finance\StoreReimburseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreReimburseRequest $request)
    {
        $data = $request->validated();
        $proofFile = $request->file('proof_file');
        unset($data['proof_file']);

        $this->reimburseService->storeReimburse($data, $proofFile);

        return redirect()
            ->route('reimburse.index')
            ->with('success', 'Pengajuan reimburse berhasil ditambahkan!');
    }

    /**
     * Memperbarui data reimburse.
     *
     * Hanya data dengan status 'draft' yang dapat diperbarui.
     *
     * @param  \App\Http\Requests\Finance\UpdateReimburseRequest $request
     * @param  \App\Models\Finance\Reimburse                     $reimburse
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateReimburseRequest $request, Reimburse $reimburse)
    {
        try {
            $data = $request->validated();
            $proofFile = $request->file('proof_file');
            unset($data['proof_file']);

            $this->reimburseService->updateReimburse($reimburse, $data, $proofFile);

            return redirect()
                ->route('reimburse.index')
                ->with('success', 'Data reimburse berhasil diperbarui!');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('reimburse.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk delete reimburse yang dipilih.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('reimburse.index')
                ->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        // Admin hanya boleh menghapus pengajuan berstatus draft
        $deletedCount = $this->reimburseService->bulkDelete($ids, $request->user()?->isAdmin() ?? false);

        return redirect()->route('reimburse.index')
            ->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Approve reimburse yang dipilih (role admin).
     *
     * Setelah disetujui, halaman index langsung membuka pratinjau dokumen
     * (PDF) reimburse yang baru disetujui di dalam halaman — tanpa tab baru.
     * Kode yang disetujui dikirim lewat flash `reimburse_preview_ids` dan
     * dibaca oleh JS halaman (window.openDocumentPreview).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()
                ->route('reimburse.index')
                ->with('error', 'Tidak ada reimburse yang dipilih!');
        }

        $approvedCodes = $this->reimburseService->bulkApprove((array) $ids);

        return redirect()
            ->route('reimburse.index')
            ->with('success', 'Reimburse berhasil disetujui!')
            ->with('reimburse_preview_ids', $approvedCodes);
    }

    /**
     * Reject reimburse yang dipilih (role super admin).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()
                ->route('reimburse.index')
                ->with('error', 'Tidak ada reimburse yang dipilih!');
        }

        $this->reimburseService->bulkReject($ids);

        return redirect()
            ->route('reimburse.index')
            ->with('success', 'Reimburse berhasil ditolak!');
    }

    /**
     * Export reimburse ke PDF.
     *
     * Tanpa `ids[]`: semua data sesuai filter (Export Semua). Dengan `ids[]`
     * (query string): hanya reimburse terpilih — dipakai pratinjau otomatis
     * setelah reimburse disetujui.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $reimburses = $this->reimburseService->getExportData($request);
        $summary = $this->reimburseService->getStatusSummary($reimburses);
        $submission = $this->reimburseService->getSubmissionSummary($request);

        $pdf = Pdf::loadView('exports.finance.reimburse-pdf', [
            'reimburses'     => $reimburses,
            'totalAmount'    => $summary['total_amount'],
            'draftCount'     => $summary['draft_count'],
            'approvedCount'  => $summary['approved_count'],
            'rejectedCount'  => $summary['rejected_count'],
            'submission'     => $submission,
            'status'         => $request->input('status'),
            'statusText'     => $this->reimburseService->buildStatusText($request, $reimburses, $submission),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Reimburse_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export reimburse ke Excel.
     *
     * Tanpa `ids[]`: semua data sesuai filter. Dengan `ids[]`: hanya data terpilih.
     *
     * @param  \Illuminate\Http\Request $request
     */
    public function exportExcel(Request $request)
    {
        $reimburses = $this->reimburseService->getExportData($request);
        $status = $request->input('status');

        return Excel::download(
            new ReimburseExport($reimburses, $status, $this->reimburseService->buildStatusText($request, $reimburses)),
            'Reimburse_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export PDF reimburse yang dipilih (checkbox `ids[]`, POST).
     *
     * Dipanggil oleh tombol "Export Dipilih" pada print-dropdown-with-selected.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function exportPdfSelected(Request $request)
    {
        if (empty($this->reimburseService->selectedIds($request))) {
            return redirect()->route('reimburse.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        return $this->exportPdf($request);
    }

    /**
     * Export Excel reimburse yang dipilih (checkbox `ids[]`, POST).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportExcelSelected(Request $request)
    {
        if (empty($this->reimburseService->selectedIds($request))) {
            return redirect()->route('reimburse.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        return $this->exportExcel($request);
    }

    /**
     * Menghitung total amount dari reimburse yang dipilih.
     *
     * API endpoint JSON untuk keperluan UI.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSelectedTotal(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['total' => 0]);
        }

        return response()->json(
            $this->reimburseService->getSelectedTotal($ids)
        );
    }
}
