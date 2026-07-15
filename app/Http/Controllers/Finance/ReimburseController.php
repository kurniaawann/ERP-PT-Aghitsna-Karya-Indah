<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreReimburseRequest;
use App\Http\Requests\Finance\UpdateReimburseRequest;
use App\Models\Finance\Reimburse;
use App\Exports\Finance\ReimburseExport;
use App\Services\Finance\ReimburseService;
use App\Traits\HasBulkActions;
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
    use HasBulkActions;

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
        $reimburses = $this->reimburseService
            ->buildFilteredQuery($request)
            ->paginate(15)
            ->withQueryString();

        $search = $request->input('search');
        $status = $request->input('status');
        $month = $request->input('month');
        $year = $request->input('year');

        return view('pages.finance.reimburse.index', compact('reimburses', 'search', 'status', 'month', 'year'));
    }

    /**
     * Menyimpan data reimburse baru.
     *
     * @param  \App\Http\Requests\Finance\StoreReimburseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreReimburseRequest $request)
    {
        $this->reimburseService->storeReimburse($request->validated());

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
            $this->reimburseService->updateReimburse($reimburse, $request->validated());

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
        return $this->destroySelectedBy(
            $request,
            Reimburse::class,
            'ids',
            'reimburse_code',
            'reimburse.index'
        );
    }

    /**
     * Approve reimburse yang dipilih (role super admin).
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

        $this->reimburseService->bulkApprove($ids);

        return redirect()
            ->route('reimburse.index')
            ->with('success', 'Reimburse berhasil disetujui!');
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
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request)
    {
        $reimburses = $this->reimburseService->getExportData($request);
        $summary = $this->reimburseService->getStatusSummary($reimburses);

        $pdf = Pdf::loadView('exports.finance.reimburse-pdf', [
            'reimburses'     => $reimburses,
            'totalAmount'    => $summary['total_amount'],
            'draftCount'     => $summary['draft_count'],
            'approvedCount'  => $summary['approved_count'],
            'rejectedCount'  => $summary['rejected_count'],
            'status'         => $request->input('status'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Reimburse_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export reimburse ke Excel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Symfony\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $reimburses = $this->reimburseService->getExportData($request);
        $status = $request->input('status');

        return Excel::download(
            new ReimburseExport($reimburses, $status),
            'Reimburse_' . date('Y-m-d') . '.xlsx'
        );
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
