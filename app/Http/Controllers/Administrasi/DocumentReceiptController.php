<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrasi\StoreDocumentReceiptRequest;
use App\Http\Requests\Administrasi\UpdateDocumentReceiptRequest;
use App\Models\Administrasi\DocumentReceipt;
use App\Services\Administrasi\DocumentReceiptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Controller untuk modul Tanda Terima Dokumen (Document Receipt).
 *
 * Controller ini hanya menangani Request dan Response.
 * Seluruh business logic telah dipindahkan ke DocumentReceiptService.
 */
class DocumentReceiptController extends Controller
{
    /**
     * Konstruktor - dependency injection DocumentReceiptService.
     *
     * @param  DocumentReceiptService  $service  Service layer untuk modul tanda terima dokumen
     */
    public function __construct(
        private readonly DocumentReceiptService $service
    ) {}

    /**
     * Menampilkan daftar tanda terima dokumen dengan filter pencarian dan paginasi.
     *
     * @param  Request  $request  Request HTTP (search parameter)
     * @return \Illuminate\View\View Halaman daftar tanda terima dokumen
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;
        $documents = $this->service->getPaginated($search, $month, $year);

        return view('pages.administrasi.document-receipt', compact('documents', 'search'));
    }

    /**
     * Menyimpan data tanda terima dokumen baru ke database.
     *
     * @param  StoreDocumentReceiptRequest  $request  Request yang sudah divalidasi
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function store(StoreDocumentReceiptRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('document-receipt.index')
            ->with('success', 'Tanda terima dokumen berhasil ditambahkan!');
    }

    /**
     * Memperbarui data tanda terima dokumen yang sudah ada.
     *
     * @param  UpdateDocumentReceiptRequest  $request  Request yang sudah divalidasi
     * @param  DocumentReceipt  $documentReceipt  Model tanda terima dokumen (route model binding by id_document)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses
     */
    public function update(UpdateDocumentReceiptRequest $request, DocumentReceipt $documentReceipt)
    {
        $this->service->update($documentReceipt, $request->validated());

        return redirect()
            ->route('document-receipt.index')
            ->with('success', 'Tanda terima dokumen berhasil diperbarui!');
    }

    /**
     * Menghapus beberapa data tanda terima dokumen sekaligus (bulk delete).
     *
     * @param  Request  $request  Request HTTP (ids parameter berisi array id_document)
     * @return \Illuminate\Http\RedirectResponse Redirect ke halaman index dengan pesan sukses/error
     */
    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()
                ->route('document-receipt.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $deletedCount = $this->service->destroySelected($ids);

        return redirect()
            ->route('document-receipt.index')
            ->with('success', "{$deletedCount} data terpilih berhasil dihapus.");
    }

    /**
     * Mengekspor seluruh data tanda terima dokumen ke format PDF.
     *
     * @param  Request  $request  Request HTTP (search parameter untuk filter)
     * @return \Symfony\Component\HttpFoundation\Response Response PDF download
     */
    public function exportPdfAll(Request $request)
    {
        $search = $request->input('search');
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;
        $documents = $this->service->getAllForExport($search, $month, $year);

        return $this->generatePdfResponse($documents);
    }

    /**
     * Mengekspor data tanda terima dokumen yang dipilih ke format PDF.
     *
     * @param  Request  $request  Request HTTP (ids parameter berisi array id_document)
     * @return \Symfony\Component\HttpFoundation\Response Response PDF download
     */
    public function exportPdfSelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()
                ->route('document-receipt.index')
                ->with('error', 'Tidak ada data yang dipilih!');
        }

        $documents = $this->service->getByIds($ids);

        return $this->generatePdfResponse($documents);
    }

    /**
     * Membuat response PDF dari koleksi data tanda terima dokumen.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $documents  Koleksi data tanda terima dokumen
     * @return \Symfony\Component\HttpFoundation\Response Response PDF download
     */
    private function generatePdfResponse($documents)
    {
        $pdf = Pdf::loadView('exports.administrasi.document-receipt-pdf', compact('documents'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Tanda_Terima_Dokumen_'.date('Y-m-d').'.pdf');
    }
}
