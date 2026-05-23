<?php

namespace App\Http\Controllers\Administrasi;

use App\Http\Controllers\Controller;
use App\Models\Administrasi\Nota;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaController extends Controller
{
    private function normalizeDecimalInput($value): float
    {
        $normalized = str_replace([' ', '.'], '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }

    public function index(Request $request)
    {
        // Ambil keyword pencarian dari request
        $search = $request->input('search');

        // Query data nota dengan filter pencarian
        $notas = Nota::when($search, function ($query, $search) {
            return $query->where('id_nota', 'like', "%{$search}%")
                ->orWhere('kepada', 'like', "%{$search}%")
                ->orWhere('faktur_no', 'like', "%{$search}%")
                ->orWhere('sj_no', 'like', "%{$search}%");
        })
            ->latest('created_at')
            ->paginate(10);

        return view('pages.administrasi.nota', compact('notas', 'search'));
    }

    public function store(Request $request)
    {
        // Validasi input dasar
        $request->validate([
            'kepada' => 'required',
            'faktur_no' => 'required',
            'sj_no' => 'required',
            'nota_date' => 'required|date',
        ]);

        // Auto-generate kode nota
        $notaCode = Nota::generateNotaCode();

        // Set default lokasi jika kosong
        $location = $request->input('location', 'Jakarta');

        // Process items array
        $items = [];
        $itemsTotal = 0;

        if ($request->has('item_banyaknya')) {
            $banyaknya = $request->input('item_banyaknya', []);
            $namaBarang = $request->input('item_nama_barang', []);
            $hargaSatuan = $request->input('item_harga_satuan', []);

            foreach ($banyaknya as $index => $qty) {
                if (!empty($qty) && !empty($namaBarang[$index])) {
                    $harga = (int) str_replace(['.', ','], '', $hargaSatuan[$index] ?? 0);
                    $jumlah = (int) $qty * $harga;
                    $itemsTotal += $jumlah;

                    $items[] = [
                        'banyaknya' => (int) $qty,
                        'nama_barang' => $namaBarang[$index],
                        'harga_satuan' => $harga,
                        'jumlah' => $jumlah,
                    ];
                }
            }
        }

        // Process optional fields
        $sewaJual = $request->input('sewa_jual') ? (int) str_replace(['.', ','], '', $request->input('sewa_jual')) : null;
        $ongkosKirim = $request->input('ongkos_kirim') ? (int) str_replace(['.', ','], '', $request->input('ongkos_kirim')) : null;
        $bongkarPasang = $request->input('bongkar_pasang') ? (int) str_replace(['.', ','], '', $request->input('bongkar_pasang')) : null;
        $lembur = $request->input('lembur') ? (int) str_replace(['.', ','], '', $request->input('lembur')) : null;
        $uangJaminan = $request->input('uang_jaminan') ? (int) str_replace(['.', ','], '', $request->input('uang_jaminan')) : null;

        // Calculate total
        $jumlahTotal = $itemsTotal + ($sewaJual ?? 0) + ($ongkosKirim ?? 0) + ($bongkarPasang ?? 0) + ($lembur ?? 0) + ($uangJaminan ?? 0);

        // Process selected payment accounts
        $selectedPaymentAccounts = $request->input('selected_payment_accounts', []);

        // Process PPN
        $ppnPercentage = $this->normalizeDecimalInput($request->input('ppn_percentage', 12));
        $ppnAmount = (int) ($jumlahTotal * ($ppnPercentage / 100));
        $totalWithPpn = $jumlahTotal + $ppnAmount;

        // Create nota
        Nota::create([
            'id_nota' => $notaCode,
            'location' => $location,
            'nota_date' => $request->input('nota_date'),
            'kepada' => $request->input('kepada'),
            'faktur_no' => $request->input('faktur_no'),
            'sj_no' => $request->input('sj_no'),
            'items' => $items,
            'penerima' => $request->input('penerima'),
            'sewa_jual' => $sewaJual,
            'ongkos_kirim' => $ongkosKirim,
            'bongkar_pasang' => $bongkarPasang,
            'lembur' => $lembur,
            'uang_jaminan' => $uangJaminan,
            'jumlah_total' => $jumlahTotal,
            'selected_payment_accounts' => $selectedPaymentAccounts,
            'ppn_percentage' => $ppnPercentage,
            'ppn_amount' => $ppnAmount,
            'total_with_ppn' => $totalWithPpn,
        ]);

        return redirect()->route('nota.administrasi.index')->with('success', 'Nota berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $nota = Nota::findOrFail($id);

        // Validasi input dasar
        $request->validate([
            'kepada' => 'required',
            'faktur_no' => 'required',
            'sj_no' => 'required',
            'nota_date' => 'required|date',
        ]);

        // Set default lokasi jika kosong
        $location = $request->input('location', 'Jakarta');

        // Process items array
        $items = [];
        $itemsTotal = 0;

        if ($request->has('item_banyaknya')) {
            $banyaknya = $request->input('item_banyaknya', []);
            $namaBarang = $request->input('item_nama_barang', []);
            $hargaSatuan = $request->input('item_harga_satuan', []);

            foreach ($banyaknya as $index => $qty) {
                if (!empty($qty) && !empty($namaBarang[$index])) {
                    $harga = (int) str_replace(['.', ','], '', $hargaSatuan[$index] ?? 0);
                    $jumlah = (int) $qty * $harga;
                    $itemsTotal += $jumlah;

                    $items[] = [
                        'banyaknya' => (int) $qty,
                        'nama_barang' => $namaBarang[$index],
                        'harga_satuan' => $harga,
                        'jumlah' => $jumlah,
                    ];
                }
            }
        }

        // Process optional fields
        $sewaJual = $request->input('sewa_jual') ? (int) str_replace(['.', ','], '', $request->input('sewa_jual')) : null;
        $ongkosKirim = $request->input('ongkos_kirim') ? (int) str_replace(['.', ','], '', $request->input('ongkos_kirim')) : null;
        $bongkarPasang = $request->input('bongkar_pasang') ? (int) str_replace(['.', ','], '', $request->input('bongkar_pasang')) : null;
        $lembur = $request->input('lembur') ? (int) str_replace(['.', ','], '', $request->input('lembur')) : null;
        $uangJaminan = $request->input('uang_jaminan') ? (int) str_replace(['.', ','], '', $request->input('uang_jaminan')) : null;

        // Calculate total
        $jumlahTotal = $itemsTotal + ($sewaJual ?? 0) + ($ongkosKirim ?? 0) + ($bongkarPasang ?? 0) + ($lembur ?? 0) + ($uangJaminan ?? 0);

        // Process selected payment accounts
        $selectedPaymentAccounts = $request->input('selected_payment_accounts', []);

        // Process PPN
        $ppnPercentage = $this->normalizeDecimalInput($request->input('ppn_percentage', 12));
        $ppnAmount = (int) ($jumlahTotal * ($ppnPercentage / 100));
        $totalWithPpn = $jumlahTotal + $ppnAmount;

        // Update nota
        $nota->update([
            'location' => $location,
            'nota_date' => $request->input('nota_date'),
            'kepada' => $request->input('kepada'),
            'faktur_no' => $request->input('faktur_no'),
            'sj_no' => $request->input('sj_no'),
            'items' => $items,
            'penerima' => $request->input('penerima'),
            'sewa_jual' => $sewaJual,
            'ongkos_kirim' => $ongkosKirim,
            'bongkar_pasang' => $bongkarPasang,
            'lembur' => $lembur,
            'uang_jaminan' => $uangJaminan,
            'jumlah_total' => $jumlahTotal,
            'selected_payment_accounts' => $selectedPaymentAccounts,
            'ppn_percentage' => $ppnPercentage,
            'ppn_amount' => $ppnAmount,
            'total_with_ppn' => $totalWithPpn,
        ]);

        return redirect()->route('nota.administrasi.index')->with('success', 'Nota berhasil diperbarui!');
    }

    public function destroySelected(Request $request)
    {
        // Ambil array id_nota dari checkbox selection
        $ids = $request->input('ids');

        // Validasi
        if (empty($ids)) {
            return redirect()->route('nota.administrasi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus nota berdasarkan id_nota
        Nota::whereIn('id_nota', $ids)->delete();

        return redirect()->route('nota.administrasi.index')->with('success', 'Nota berhasil dihapus!');
    }

    /**
     * Export notas to PDF (dinamis untuk all atau selected)
     */
    public function exportPdfAll(Request $request)
    {
        // Ambil filter dari request
        $search = $request->input('search');

        // Query nota dengan filter yang sama seperti di index
        $notas = Nota::when($search, function ($query, $search) {
            return $query->where('id_nota', 'like', "%{$search}%")
                ->orWhere('kepada', 'like', "%{$search}%")
                ->orWhere('faktur_no', 'like', "%{$search}%");
        })
            ->latest('created_at')
            ->get();

        // Generate PDF dengan 1 file template yang sama
        $pdf = Pdf::loadView('exports.administrasi.nota-pdf', compact('notas'));

        $filename = 'nota-administrasi-' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export selected notas to PDF (dinamis untuk 1 atau banyak)
     */
    public function exportPdfSelected(Request $request)
    {
        // Ambil array ID dari form
        $ids = $request->input('ids');

        if (empty($ids)) {
            return redirect()->route('nota.administrasi.index')->with('error', 'Tidak ada data yang dipilih!');
        }

        // Query nota berdasarkan id yang dipilih
        $notas = Nota::whereIn('id_nota', $ids)
            ->latest('created_at')
            ->get();

        // Generate PDF dengan 1 file template yang sama
        // Otomatis handle 1 nota atau multiple notas
        $pdf = Pdf::loadView('exports.administrasi.nota-pdf', compact('notas'));

        // Generate filename yang aman (tanpa karakter "/" atau "\")
        if (count($ids) == 1) {
            // Untuk 1 nota, gunakan ID yang sudah di-sanitize
            $safeId = str_replace(['/', '\\'], '-', $ids[0]);
            $filename = 'nota-' . $safeId . '.pdf';
        } else {
            // Untuk multiple notas, gunakan timestamp
            $filename = 'nota-selected-' . date('Y-m-d') . '.pdf';
        }

        return $pdf->download($filename);
    }
}
