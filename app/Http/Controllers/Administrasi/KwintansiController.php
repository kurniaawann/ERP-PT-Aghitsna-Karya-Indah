<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\Kwintansi;
use App\Models\Finance\PaymentAccount;
use App\Services\InputNormalizer;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KwintansiController extends Controller
{
    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data kwintansi dengan filter pencarian
        $kwintansis = Kwintansi::with('paymentAccount')
            ->when($search, function ($query, $search) {
                return $query->where('id_kwintansi', 'like', "%{$search}%")
                    ->orWhere('received_from', 'like', "%{$search}%")
                    ->orWhere('payment_for', 'like', "%{$search}%");
            })
            ->latest('created_at')
            ->paginate(15);

        return view('pages.administrasi.kwintansi', compact('kwintansis', 'search'));
    }

    public function store(Request $request)
    {
        // Ambil semua input dari form
        $data = $request->all();

        // Auto-generate kode kwintansi
        $data['id_kwintansi'] = Kwintansi::generateKwintansiCode();

        // Set default lokasi jika kosong
        if (empty($data['location'])) {
            $data['location'] = 'Depok';
        }

        // Konversi include_bank checkbox
        $data['include_bank'] = $request->has('include_bank');

        // Normalisasi format rupiah
        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);
        $data['remaining'] = InputNormalizer::normalizeCurrency($data['remaining'] ?? 0);

        // Insert data kwintansi ke database
        Kwintansi::create($data);

        return redirect()->route('kwintansi.index')->with('success', 'Kwintansi berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $kwintansi = Kwintansi::findOrFail($id);

        // Ambil semua input dari form
        $data = $request->all();

        // Set default lokasi jika kosong
        if (empty($data['location'])) {
            $data['location'] = 'Depok';
        }

        // Konversi include_bank checkbox
        $data['include_bank'] = $request->has('include_bank');

        // Normalisasi format rupiah
        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);
        $data['remaining'] = InputNormalizer::normalizeCurrency($data['remaining'] ?? 0);

        // Update data kwintansi
        $kwintansi->update($data);

        return redirect()->route('kwintansi.index')->with('success', 'Kwintansi berhasil diperbarui!');
    }

    public function destroySelected(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('kwintansi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        DB::beginTransaction();
        try {
            Kwintansi::whereIn('id_kwintansi', $ids)->get()->each->delete();

            DB::commit();

            return redirect()->route('kwintansi.index')
                ->with('success', count($ids) . ' data berhasil dihapus!');
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Kwintansi destroySelected failed', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
        }
    }

    /**
     * Export all kwintansi to PDF
     */
    public function exportPdfAll(Request $request)
    {
        // Ambil filter dari request
        $search = $request->input('search');

        // Query kwintansi dengan filter yang sama seperti di index
        $kwintansis = Kwintansi::with('paymentAccount')
            ->when($search, function ($query, $search) {
                return $query->where('id_kwintansi', 'like', "%{$search}%")
                    ->orWhere('received_from', 'like', "%{$search}%")
                    ->orWhere('payment_for', 'like', "%{$search}%");
            })
            ->latest('created_at')
            ->get();

        // Load view PDF
        $pdf = Pdf::loadView('exports.administrasi.kwintansi-pdf', compact('kwintansis'));

        // Set paper size dan orientation
        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        return $pdf->download('Kwitansi_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export selected kwintansi to PDF
     */
    public function exportPdfSelected(Request $request)
    {
        // Ambil array id_kwintansi dari request
        $ids = $request->input('ids');

        // Validasi
        if (empty($ids)) {
            return redirect()->route('kwintansi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Query kwintansi berdasarkan id yang dipilih
        $kwintansis = Kwintansi::with('paymentAccount')
            ->whereIn('id_kwintansi', $ids)
            ->orderBy('created_at', 'desc')
            ->get();

        // Load view PDF
        $pdf = Pdf::loadView('exports.administrasi.kwintansi-pdf', compact('kwintansis'));

        // Set paper size dan orientation
        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        return $pdf->download('Kwitansi_' . date('Y-m-d') . '.pdf');
    }
}
