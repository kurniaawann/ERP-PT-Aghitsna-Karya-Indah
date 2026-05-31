<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\DocumentReceipt;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HasBulkActions;

class DocumentReceiptController extends Controller
{
    use HasBulkActions;
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data dokumen dengan filter pencarian
        $documents = DocumentReceipt::when($search, function ($query, $search) {
            return $query->where('id_document', 'like', "%{$search}%")
                ->orWhere('received_from', 'like', "%{$search}%")
                ->orWhere('regarding', 'like', "%{$search}%");
        })
            ->latest('created_at')
            ->paginate(10);

        return view('pages.administrasi.document-receipt', compact('documents', 'search'));
    }

    public function store(Request $request)
    {
        // Ambil semua input dari form
        $data = $request->all();

        // Auto-generate kode dokumen
        $data['id_document'] = DocumentReceipt::generateDocumentCode();

        // Set default lokasi jika kosong
        if (empty($data['location'])) {
            $data['location'] = 'Depok';
        }

        // Insert data dokumen ke database
        DocumentReceipt::create($data);

        return redirect()->route('document-receipt.index')->with('success', 'Tanda terima dokumen berhasil ditambahkan!');
    }

    public function update(Request $request, DocumentReceipt $documentReceipt)
    {
        // Ambil semua input dari form
        $data = $request->all();

        // Set default lokasi jika kosong
        if (empty($data['location'])) {
            $data['location'] = 'Depok';
        }

        // Update data dokumen
        $documentReceipt->update($data);

        return redirect()->route('document-receipt.index')->with('success', 'Tanda terima dokumen berhasil diperbarui!');
    }

    public function destroySelected(Request $request)
    {
        // Ambil array id_document dari checkbox selection
        $ids = $request->input('ids');

        // Validasi
        if (empty($ids)) {
            return redirect()->route('document-receipt.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        return $this->destroySelectedBy($request, DocumentReceipt::class, 'ids', 'id_document', 'document-receipt.index');
    }

    /**
     * Export all documents to PDF
     */
    public function exportPdfAll(Request $request)
    {
        // Ambil filter dari request
        $search = $request->input('search');

        // Query dokumen dengan filter yang sama seperti di index
        $documents = DocumentReceipt::query()
            ->when($search, function ($query, $search) {
                return $query->where('id_document', 'like', "%{$search}%")
                    ->orWhere('received_from', 'like', "%{$search}%")
                    ->orWhere('regarding', 'like', "%{$search}%");
            })
            ->latest('created_at')
            ->get();

        // Load view PDF - gunakan template yang sama untuk semua kondisi
        $pdf = Pdf::loadView('exports.administrasi.document-receipt-pdf', compact('documents'));

        // Set paper size dan orientation
        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        return $pdf->download('tanda_terima_dokumen_' . date('YmdHis') . '.pdf');
    }

    /**
     * Export selected documents to PDF
     */
    public function exportPdfSelected(Request $request)
    {
        // Ambil array id_document dari request
        $ids = $request->input('ids');

        // Validasi
        if (empty($ids)) {
            return redirect()->route('document-receipt.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Query dokumen berdasarkan id yang dipilih
        $documents = DocumentReceipt::whereIn('id_document', $ids)
            ->orderBy('created_at', 'desc')
            ->get();

        // Load view PDF
        $pdf = Pdf::loadView('exports.administrasi.document-receipt-pdf', compact('documents'));

        // Set paper size dan orientation
        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        return $pdf->download('tanda_terima_dokumen_selected_' . date('YmdHis') . '.pdf');
    }
}
