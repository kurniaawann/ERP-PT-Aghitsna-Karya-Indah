<?php

namespace App\Http\Controllers\Administrasi;

use App\Exports\Administrasi\RABExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\RABStoreRequest;
use App\Http\Requests\Administrasi\RABUpdateRequest;
use App\Models\Administrasi\RAB;
use App\Services\Administrasi\RABService;
use App\Services\Finance\PaymentAccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    protected PaymentAccountService $paymentAccountService;

    public function __construct(RABService $rabService, PaymentAccountService $paymentAccountService)
    {
        $this->rabService = $rabService;
        $this->paymentAccountService = $paymentAccountService;
    }

    /**
     * Menampilkan daftar RAB dengan pagination dan search.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $rabs = $this->rabService->getPaginatedRABs($search);
        $paymentAccounts = $this->paymentAccountService->getActiveAccounts();

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
     * @return \Illuminate\View\View
     */
    public function show(string $rabNumber)
    {
        $rab = $this->rabService->getRABWithDetails($rabNumber);
        $paymentAccounts = $this->paymentAccountService->getActiveAccounts();

        return view('components.administrasi.RAB.RABDetail', compact('rab', 'paymentAccounts'));
    }

    /**
     * AJAX: mendapatkan data RAB untuk form edit.
     *
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(RABStoreRequest $request)
    {
        $rabData = json_decode($request->input('rab_data'), true);
        if (! $rabData || ! is_array($rabData) || count($rabData) === 0) {
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(RABUpdateRequest $request, string $rabNumber)
    {
        $rabData = json_decode($request->input('rab_data'), true);
        if (! $rabData || ! is_array($rabData) || count($rabData) === 0) {
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
     * Penghapusan diblokir bila Rekap Proyek terkait masih digunakan data lain
     * (Laporan Keuangan berisi transaksi, payroll, kasbon, atau karyawan) agar
     * data transaksi riil tidak hilang diam-diam lewat cascade.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $rabNumbers = $request->input('selected_items', []);

        if (empty($rabNumbers)) {
            return back()->with('error', 'Pilih minimal 1 RAB untuk dihapus.');
        }

        try {
            $count = $this->rabService->destroyRABs($rabNumbers);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('RAB destroy failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }

        return back()->with('success', "{$count} RAB berhasil dihapus.");
    }

    /**
     * Export PDF RAB.
     *
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
