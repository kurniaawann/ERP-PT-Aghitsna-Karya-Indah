<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreDeliveryNoteRequest;
use App\Http\Requests\Administrasi\UpdateDeliveryNoteRequest;
use App\Models\Administrasi\DeliveryNote;
use App\Services\Administrasi\DeliveryNoteService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HasBulkActions;

/**
 * Controller untuk modul Surat Jalan (Delivery Note).
 *
 * Menangani HTTP request/response untuk CRUD surat jalan,
 * pencarian, penghapusan massal, dan export PDF.
 * Business logic dialihkan ke DeliveryNoteService.
 *
 * @package App\Http\Controllers\Administrasi
 */
class DeliveryNoteController extends Controller
{
    use HasBulkActions;

    /**
     * @var DeliveryNoteService Service layer untuk surat jalan
     */
    protected DeliveryNoteService $deliveryNoteService;

    /**
     * Konstruktor - inject DeliveryNoteService.
     *
     * @param  DeliveryNoteService  $deliveryNoteService  Service surat jalan
     */
    public function __construct(DeliveryNoteService $deliveryNoteService)
    {
        $this->deliveryNoteService = $deliveryNoteService;
    }

    /**
     * Menampilkan daftar surat jalan dengan fitur pencarian dan paginasi.
     *
     * @param  \Illuminate\Http\Request  $request  Request HTTP
     * @return \Illuminate\View\View    Halaman daftar surat jalan
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $deliveryNotes = $this->deliveryNoteService->getPaginated($search);

        return view('pages.administrasi.delivery-note', compact('deliveryNotes', 'search'));
    }

    /**
     * Menyimpan data surat jalan baru ke database.
     *
     * @param  StoreDeliveryNoteRequest  $request  Request dengan data yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse  Redirect ke halaman index dengan pesan sukses
     */
    public function store(StoreDeliveryNoteRequest $request)
    {
        $this->deliveryNoteService->create($request->validated());

        return redirect()->route('delivery-note.administrasi.index')->with('success', 'Surat Jalan berhasil ditambahkan!');
    }

    /**
     * Memperbarui data surat jalan yang sudah ada.
     *
     * @param  UpdateDeliveryNoteRequest  $request  Request dengan data yang sudah divalidasi
     * @param  string  $id  ID surat jalan yang akan diperbarui
     * @return \Illuminate\Http\RedirectResponse  Redirect ke halaman index dengan pesan sukses
     */
    public function update(UpdateDeliveryNoteRequest $request, $id)
    {
        $deliveryNote = DeliveryNote::findOrFail($id);
        $this->deliveryNoteService->update($deliveryNote, $request->validated());

        return redirect()->route('delivery-note.administrasi.index')->with('success', 'Surat Jalan berhasil diperbarui!');
    }

    /**
     * Menghapus beberapa surat jalan sekaligus (bulk delete).
     *
     * @param  \Illuminate\Http\Request  $request  Request HTTP dengan array ID surat jalan
     * @return \Illuminate\Http\RedirectResponse  Redirect ke halaman index dengan pesan sukses/error
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('delivery-note.administrasi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        return $this->destroySelectedBy($request, DeliveryNote::class, 'ids', 'id_delivery_note', 'delivery-note.administrasi.index');
    }

    /**
     * Export semua data surat jalan ke PDF.
     *
     * @param  \Illuminate\Http\Request  $request  Request HTTP dengan parameter pencarian opsional
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse  File PDF yang di-download
     */
    public function exportPdfAll(Request $request)
    {
        $search = $request->input('search');
        $deliveryNotes = $this->deliveryNoteService->getAllForExport($search);

        $pdf = Pdf::loadView('exports.administrasi.delivery-note-pdf', compact('deliveryNotes'));

        return $pdf->download('Surat_Jalan_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export surat jalan yang dipilih ke PDF.
     *
     * @param  \Illuminate\Http\Request  $request  Request HTTP dengan array ID surat jalan yang dipilih
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse  File PDF atau redirect dengan pesan error
     */
    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('delivery-note.administrasi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        $deliveryNotes = $this->deliveryNoteService->getByIds($ids);

        $pdf = Pdf::loadView('exports.administrasi.delivery-note-pdf', compact('deliveryNotes'));

        if (count($ids) == 1) {
            $safeId = str_replace(['/', '\\'], '-', $ids[0]);
            $filename = "Surat_Jalan_{$safeId}_" . date('Y-m-d') . '.pdf';
        } else {
            $filename = 'Surat_Jalan_' . date('Y-m-d') . '.pdf';
        }

        return $pdf->download($filename);
    }
}
