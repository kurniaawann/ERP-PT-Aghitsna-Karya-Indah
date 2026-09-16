<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreCementDeliveryOrderRequest;
use App\Http\Requests\Inventory\UpdateCementDeliveryOrderRequest;
use App\Http\Requests\Inventory\GenerateSemenInvoiceRequest;
use App\Models\Sdm\Executive;
use App\Services\Finance\PaymentAccountService;
use App\Services\Finance\SemenInvoiceService;
use App\Services\Inventory\CementDeliveryOrderService;
use App\Exports\Inventory\CementDeliveryOrderExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk mengelola DO Semen (Delivery Order Semen).
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic didelegasikan ke CementDeliveryOrderService.
 */
class CementDeliveryOrderController extends Controller
{
    public function __construct(
        private readonly CementDeliveryOrderService $cementDeliveryOrderService,
        private readonly SemenInvoiceService $semenInvoiceService,
        private readonly PaymentAccountService $paymentAccountService
    ) {}

    /**
     * Menampilkan halaman tab DO Semen dengan paginasi, pencarian,
     * dan filter bulan/tahun.
     *
     * Invoice Semen kini digenerate langsung dari baris DO Semen sehingga
     * halaman hanya berisi satu tab: DO Semen. Data pendukung modal
     * (executives & rekening pembayaran) ikut disiapkan untuk tombol
     * "Buat Invoice" pada tiap baris DO.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $cementDeliveryOrders = $this->cementDeliveryOrderService->getPaginatedSearch(
            $request->input('search'),
            $request->input('month'),
            $request->input('year')
        );

        $paymentAccounts = $this->paymentAccountService->getActiveAccounts();
        $executives = Executive::where('created_by', auth()->id())->orderBy('name')->get();

        return view('pages.inventory.cement-do', compact('cementDeliveryOrders', 'paymentAccounts', 'executives'));
    }

    /**
     * Menyimpan DO Semen baru.
     *
     * @param  StoreCementDeliveryOrderRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreCementDeliveryOrderRequest $request)
    {
        $this->cementDeliveryOrderService->store($request->validated());

        return redirect()->back()->with('success', 'Data berhasil ditambahkan!');
    }

    /**
     * Memperbarui DO Semen yang sudah ada.
     *
     * @param  UpdateCementDeliveryOrderRequest  $request
     * @param  string                            $no
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateCementDeliveryOrderRequest $request, string $no)
    {
        $cementDeliveryOrder = $this->cementDeliveryOrderService->findById($no);

        if (!$cementDeliveryOrder) {
            abort(404);
        }

        $this->cementDeliveryOrderService->update($cementDeliveryOrder, $request->validated());

        return redirect()->back()->with('success', 'Data berhasil diupdate!');
    }

    /**
     * Membuat Invoice Semen dari DO Semen (alur baru, superadmin only).
     *
     * Data semen terpilih menghasilkan satu invoice semen + satu nota
     * proyek otomatis per proyek. Baris Data Semen yang sudah pernah
     * masuk invoice lain otomatis dilewati.
     *
     * @param  GenerateSemenInvoiceRequest  $request
     * @param  string                       $no  Nomor DO Semen.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateInvoice(GenerateSemenInvoiceRequest $request, string $no)
    {
        $cementDeliveryOrder = $this->cementDeliveryOrderService->findById($no);

        if (!$cementDeliveryOrder) {
            abort(404);
        }

        $cements = $cementDeliveryOrder->cements
            ->whereIn('no', $request->validated()['cement_nos'])
            ->whereNull('invoice_number')
            ->values();

        if ($cements->isEmpty()) {
            return back()->with('error', 'Pilih minimal satu data semen yang belum diinvois.');
        }

        $this->semenInvoiceService->createFromDo($cementDeliveryOrder, $request->validated(), $cements->all());

        return back()->with('success', 'Invoice semen dan nota proyek berhasil dibuat dari DO ' . $cementDeliveryOrder->no . '!');
    }

    /**
     * Menghapus beberapa DO Semen sekaligus (bulk delete).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroySelected(Request $request)
    {
        $nos = $request->input('selected_items', []);

        if (empty($nos)) {
            return back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $deletedCount = $this->cementDeliveryOrderService->destroySelected($nos);

        return back()->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Export DO Semen ke format PDF.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf()
    {
        $groupedDeliveryOrders = $this->cementDeliveryOrderService->getGroupedByMonth();

        $pdf = Pdf::loadView('exports.inventory.cement-do-pdf', compact('groupedDeliveryOrders'));

        return $pdf->download('DO_Semen_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export DO Semen ke format Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        return Excel::download(new CementDeliveryOrderExport, 'DO_Semen_' . date('Y-m-d') . '.xlsx');
    }
}
