<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreKwitansiRequest;
use App\Http\Requests\Administrasi\UpdateKwitansiRequest;
use App\Models\Administrasi\Kwintansi;
use App\Models\Sdm\Executive;
use App\Services\Administrasi\KwintansiService;
use App\Services\Finance\PaymentAccountService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller untuk modul Kwitansi.
 *
 * Controller ini menangani request dan response HTTP.
 * Business logic dikelola di dalam KwintansiService.
 */
class KwintansiController extends Controller
{
    /**
     * Konstruktor dengan dependency injection KwintansiService.
     *
     * @param  KwintansiService  $service  Service layer modul kwitansi
     */
    public function __construct(
        private readonly KwintansiService $service,
        private readonly PaymentAccountService $paymentAccountService
    ) {}

    /**
     * Menampilkan daftar kwitansi dengan filter pencarian dan paginasi.
     *
     * @param  Request  $request  Request HTTP (search parameter)
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $isSuperadmin = auth()->user()?->role === 'superadmin';
        $invoiceType = $isSuperadmin ? $request->input('invoice_type') : null;

        $kwintansis = $this->service->getPaginated($search, $invoiceType);
        $executives = Executive::query()
            ->where('created_by', auth()->id())
            ->orderBy('name')
            ->get();
        $paymentAccounts = $this->paymentAccountService->getActiveAccounts();

        return view('pages.administrasi.kwintansi', compact('kwintansis', 'search', 'executives', 'paymentAccounts', 'invoiceType'));
    }

    /**
     * Menyimpan data kwitansi baru ke database.
     *
     * @param  StoreKwitansiRequest  $request  Request HTTP yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreKwitansiRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Kwitansi berhasil ditambahkan!');
    }

    /**
     * Memperbarui data kwitansi yang sudah ada.
     *
     * @param  UpdateKwitansiRequest  $request  Request HTTP yang sudah divalidasi
     * @param  string                 $id       ID kwitansi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateKwitansiRequest $request, $id)
    {
        $kwintansi = Kwintansi::findOrFail($id);
        $this->service->update($kwintansi, $request->validated());

        return redirect()->back()->with('success', 'Kwitansi berhasil diperbarui!');
    }

    /**
     * Menghapus beberapa data kwitansi sekaligus (bulk delete).
     *
     * @param  Request  $request  Request HTTP (ids parameter)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('kwintansi.index')
                ->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $deletedCount = $this->service->destroySelected($ids);

        $autoCount = Kwintansi::whereIn('id_kwintansi', $ids)
            ->where('created_by', auth()->id())
            ->whereNotNull('payment_proof_id')
            ->count();

        if ($deletedCount > 0 && $autoCount > 0) {
            return redirect()->route('kwintansi.index')
                ->with('success', "{$deletedCount} data terpilih berhasil dihapus. {$autoCount} kwitansi otomatis (dari bukti pembayaran) tidak dapat dihapus langsung.");
        }

        if ($deletedCount > 0) {
            return redirect()->route('kwintansi.index')
                ->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
        }

        if ($autoCount > 0) {
            return redirect()->route('kwintansi.index')
                ->with('error', "{$autoCount} kwitansi terpilih tidak dapat dihapus karena dibuat otomatis dari bukti pembayaran.");
        }

        return redirect()->route('kwintansi.index')
            ->with('error', 'Tidak ada data yang dapat dihapus.');
    }

    /**
     * Mengekspor seluruh data kwitansi ke format PDF.
     *
     * @param  Request  $request  Request HTTP (search parameter)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportPdfAll(Request $request)
    {
        $invoiceType = auth()->user()?->role === 'superadmin' ? $request->input('invoice_type') : null;
        $kwintansis = $this->service->getAllForExport($request->input('search'), $invoiceType);
        $pdf = Pdf::loadView('exports.administrasi.kwintansi-pdf', compact('kwintansis'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Kwitansi_'.date('Y-m-d').'.pdf');
    }

    /**
     * Mengekspor data kwitansi yang dipilih ke format PDF.
     *
     * @param  Request  $request  Request HTTP (ids parameter)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih!');
        }

        $kwintansis = $this->service->getByIds($ids);
        $pdf = Pdf::loadView('exports.administrasi.kwintansi-pdf', compact('kwintansis'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Kwitansi_'.date('Y-m-d').'.pdf');
    }
}
