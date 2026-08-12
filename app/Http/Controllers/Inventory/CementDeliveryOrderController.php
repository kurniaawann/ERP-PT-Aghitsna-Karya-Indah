<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreCementDeliveryOrderRequest;
use App\Http\Requests\Inventory\UpdateCementDeliveryOrderRequest;
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
        private readonly CementDeliveryOrderService $cementDeliveryOrderService
    ) {}

    /**
     * Menampilkan daftar DO Semen dengan paginasi dan pencarian.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $cementDeliveryOrders = $this->cementDeliveryOrderService->getPaginatedSearch($request->input('search'));

        return view('pages.inventory.cement-do', compact('cementDeliveryOrders'));
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
        $cementDeliveryOrders = $this->cementDeliveryOrderService->getAll();

        $pdf = Pdf::loadView('exports.inventory.cement-do-pdf', compact('cementDeliveryOrders'));

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
