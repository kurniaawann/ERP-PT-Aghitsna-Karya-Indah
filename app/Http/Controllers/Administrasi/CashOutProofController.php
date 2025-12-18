<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\CashOutProof;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CashOutProofController extends Controller
{
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data bukti kas keluar dengan filter pencarian
        $cashOuts = CashOutProof::when($search, function ($query, $search) {
            return $query->where('bkk_no', 'like', "%{$search}%")
                ->orWhere('cek_no', 'like', "%{$search}%")
                ->orWhere('paid_to', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        })
            ->latest('created_at')
            ->paginate(10);

        return view('pages.administrasi.cash-out-proof', compact('cashOuts', 'search'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'paid_to' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'director' => 'nullable|string|max:255',
            'finance_head' => 'nullable|string|max:255',
        ]);

        // Ambil semua input dari form
        $data = $request->all();

        // Auto-generate nomor BKK dan CEK
        $data['bkk_no'] = CashOutProof::generateBkkNo();
        $data['cek_no'] = CashOutProof::generateCekNo();

        // Set default untuk director dan finance_head jika kosong
        if (empty($data['director'])) {
            $data['director'] = 'Zulkarnain,ST.,MT';
        }
        if (empty($data['finance_head'])) {
            $data['finance_head'] = 'Kamila,AMK';
        }

        // Insert data ke database
        CashOutProof::create($data);

        return redirect()->route('cash-out-proof.index')->with('success', 'Bukti kas keluar berhasil ditambahkan!');
    }

    public function update(Request $request, CashOutProof $cashOutProof)
    {
        // Validasi input
        $request->validate([
            'paid_to' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'director' => 'nullable|string|max:255',
            'finance_head' => 'nullable|string|max:255',
        ]);

        // Ambil semua input dari form
        $data = $request->all();

        // Set default untuk director dan finance_head jika kosong
        if (empty($data['director'])) {
            $data['director'] = 'Zulkarnain,ST.,MT';
        }
        if (empty($data['finance_head'])) {
            $data['finance_head'] = 'Kamila,AMK';
        }

        // Update data
        $cashOutProof->update($data);

        return redirect()->route('cash-out-proof.index')->with('success', 'Bukti kas keluar berhasil diperbarui!');
    }

    public function destroySelected(Request $request)
    {
        // Ambil array bkk_no dari checkbox selection
        $ids = $request->input('ids');

        // Validasi
        if (empty($ids)) {
            return redirect()->route('cash-out-proof.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus data berdasarkan bkk_no
        CashOutProof::whereIn('bkk_no', $ids)->delete();

        return redirect()->route('cash-out-proof.index')->with('success', 'Bukti kas keluar berhasil dihapus!');
    }

    /**
     * Export all cash out proofs to PDF
     */
    public function exportPdfAll(Request $request)
    {
        // Ambil filter dari request
        $search = $request->input('search');

        // Query data dengan filter yang sama seperti di index
        $cashOuts = CashOutProof::query()
            ->when($search, function ($query, $search) {
                return $query->where('bkk_no', 'like', "%{$search}%")
                    ->orWhere('cek_no', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest('created_at')
            ->get();

        // Load view PDF dengan 2 form per halaman
        $pdf = Pdf::loadView('exports.administrasi.cash-out-proof-pdf', compact('cashOuts'));

        // Set paper size dan orientation
        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        return $pdf->download('bukti_kas_keluar_' . date('YmdHis') . '.pdf');
    }

    /**
     * Export selected cash out proofs to PDF
     */
    public function exportPdfSelected(Request $request)
    {
        // Ambil array bkk_no dari request
        $ids = $request->input('ids');

        // Validasi
        if (empty($ids)) {
            return redirect()->route('cash-out-proof.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Query data berdasarkan id yang dipilih
        $cashOuts = CashOutProof::whereIn('bkk_no', $ids)
            ->orderBy('created_at', 'desc')
            ->get();

        // Load view PDF dengan 2 form per halaman
        $pdf = Pdf::loadView('exports.administrasi.cash-out-proof-pdf', compact('cashOuts'));

        // Set paper size dan orientation
        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        return $pdf->download('bukti_kas_keluar_selected_' . date('YmdHis') . '.pdf');
    }
}
