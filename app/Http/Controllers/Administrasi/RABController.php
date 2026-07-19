<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\RAB;
use App\Models\Finance\PaymentAccount;
use App\Services\Administrasi\RABService;
use App\Http\Requests\Administrasi\RABStoreRequest;
use App\Http\Requests\Administrasi\RABUpdateRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Exports\Administrasi\RABExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * RAB Controller
 *
 * Controller untuk modul Rencana Anggaran Biaya (RAB).
 * Hanya menangani HTTP Request/Response, redirect, return view.
 * Business logic didelegasikan ke RABService.
 */
class RABController extends Controller
{
    protected RABService $rabService;

    public function __construct(RABService $rabService)
    {
        $this->rabService = $rabService;
    }

    /**
     * Menampilkan daftar RAB dengan pagination dan search.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $rabs = $this->rabService->getPaginatedRABs($search);
        $paymentAccounts = PaymentAccount::active()->get();

        return view('pages.administrasi.RAB', compact('rabs', 'paymentAccounts', 'search'));
    }

    /**
     * AJAX: mendapatkan nomor RAB berikutnya.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextRABNumber()
    {
        return response()->json([
            'rab_number' => RAB::generateRABNumber(),
        ]);
    }

    /**
     * Menampilkan detail RAB secara lengkap.
     *
     * @param string $rabNumber
     * @return \Illuminate\View\View
     */
    public function show(string $rabNumber)
    {
        $rab = $this->rabService->getRABWithDetails($rabNumber);
        $paymentAccounts = PaymentAccount::active()->get();

        return view('components.administrasi.RAB.RABDetail', compact('rab', 'paymentAccounts'));
    }

    /**
     * AJAX: mendapatkan data RAB untuk form edit.
     *
     * @param string $rabNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(string $rabNumber)
    {
        $data = $this->rabService->getRABEditData($rabNumber);

        return response()->json($data);
    }

    /**
     * Menyimpan RAB baru.
     *
     * @param RABStoreRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(RABStoreRequest $request)
    {
        $rabData = json_decode($request->input('rab_data'), true);
        if (!$rabData || !is_array($rabData) || count($rabData) === 0) {
            return back()->with('error', 'Minimal 1 kategori pekerjaan harus ditambahkan.')
                ->withInput();
        }

        $miscCostsData = [];
        if ($request->input('misc_costs_data')) {
            $miscCostsData = json_decode($request->input('misc_costs_data'), true) ?? [];
        }

        $rab = $this->rabService->storeRAB(
            $request->validated(),
            $rabData,
            $miscCostsData
        );

        $checkNumber = $rab->rab_number ?? '';
        return redirect()->route('rab.index')
            ->with('success', "RAB {$checkNumber} berhasil ditambahkan!");
    }

    /**
     * Memperbarui RAB yang sudah ada.
     *
     * @param RABUpdateRequest $request
     * @param string $rabNumber
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(RABUpdateRequest $request, string $rabNumber)
    {
        $rabData = json_decode($request->input('rab_data'), true);
        if (!$rabData || !is_array($rabData) || count($rabData) === 0) {
            return back()->with('error', 'Minimal 1 kategori pekerjaan harus ditambahkan.')
                ->withInput();
        }

        $miscCostsData = [];
        if ($request->input('misc_costs_data')) {
            $miscCostsData = json_decode($request->input('misc_costs_data'), true) ?? [];
        }

        $rab = $this->rabService->updateRAB(
            $rabNumber,
            $request->validated(),
            $rabData,
            $miscCostsData
        );

        return redirect()->route('rab.index')
            ->with('success', "RAB {$rab->rab_number} berhasil diperbarui!");
    }

    /**
     * Menghapus RAB yang dipilih.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $rabNumbers = $request->input('selected_items', []);

        if (empty($rabNumbers)) {
            return back()->with('error', 'Pilih minimal 1 RAB untuk dihapus.');
        }

        $count = $this->rabService->destroyRABs($rabNumbers);

        return back()->with('success', "{$count} RAB berhasil dihapus.");
    }

    /**
     * Export PDF RAB.
     *
     * @param string $rabNumber
     * @return \Illuminate\Http\Response
     */
    public function exportPDF(string $rabNumber)
    {
        $rab = $this->rabService->getRABWithDetails($rabNumber);
        $safeFileName = str_replace('/', '-', $rabNumber);

        $pdf = Pdf::loadView('exports.administrasi.rab-pdf', [
            'rab' => $rab,
        ])->setPaper('a4', 'portrait');

        $date = date('Y-m-d');
        return $pdf->download("RAB_{$safeFileName}_{$date}.pdf");
    }

    /**
     * Export Excel RAB.
     *
     * @param string $rabNumber
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(string $rabNumber)
    {
        $rab = RAB::where('rab_number', $rabNumber)->firstOrFail();
        $safeFileName = str_replace('/', '-', $rabNumber);
        $date = date('Y-m-d');

        return Excel::download(new RABExport($rab->rab_number), "RAB_{$safeFileName}_{$date}.xlsx");
    }
}
