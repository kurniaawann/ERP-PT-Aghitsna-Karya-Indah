<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\DocumentReceipt;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentReceiptController extends Controller
{
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
            ->paginate(15);

        return view('pages.administrasi.document-receipt', compact('documents', 'search'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'received_from' => 'required|string|max:255',
            'regarding' => 'required|string|max:255',
            'form_of' => 'required|string|max:255',
            'receipt_date' => 'required|date',
            'receipt_time' => 'required|date_format:H:i',
            'location' => 'nullable|string|max:100',
        ]);

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
        // Validasi input
        $request->validate([
            'received_from' => 'required|string|max:255',
            'regarding' => 'required|string|max:255',
            'form_of' => 'required|string|max:255',
            'receipt_date' => 'required|date',
            'receipt_time' => 'required|date_format:H:i',
            'location' => 'nullable|string|max:100',
        ]);

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
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('document-receipt.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        DB::beginTransaction();
        try {
            DocumentReceipt::whereIn('id_document', $ids)->get()->each->delete();

            DB::commit();

            return redirect()->route('document-receipt.index')
                ->with('success', count($ids) . ' data berhasil dihapus!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Document Receipt destroySelected failed', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
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
        return $pdf->download('Tanda_Terima_Dokumen_' . date('Y-m-d') . '.pdf');
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
        return $pdf->download('Tanda_Terima_Dokumen_' . date('Y-m-d') . '.pdf');
    }
}
