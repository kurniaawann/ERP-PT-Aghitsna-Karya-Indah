<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Reimburse;
use App\Exports\Finance\ReimburseExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\HasBulkActions;

class ReimburseController extends Controller
{
    use HasBulkActions;
    /**
     * Display a listing of the resource.
     * Menampilkan halaman index reimburse dengan filter & search
     */
    public function index(Request $request)
    {
        // Ambil parameter filter dari request
        $search = $request->input('search');
        $status = $request->input('status');

        // Query reimburse
        $reimburses = Reimburse::query()
            // Filter pencarian (project name atau reimburse code)
            ->when($search, function ($query, $search) {
                return $query->where('project_name', 'like', "%{$search}%")
                    ->orWhere('reimburse_code', 'like', "%{$search}%");
            })
            // Filter status
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            // Urutkan berdasarkan tanggal terbaru
            ->latest('date')
            ->paginate(10);

        // Return view dengan data reimburses
        return view('pages.finance.reimburse', compact('reimburses', 'search', 'status'));
    }

    /**
     * Store a newly created resource in storage.
     * Menyimpan data reimburse baru (role admin)
     */
    public function store(Request $request)
    {
        // Ambil semua data dari form
        $data = $request->all();

        // Auto-generate reimburse code
        $data['reimburse_code'] = Reimburse::generateReimburseCode();

        // Set default status = draft
        $data['status'] = 'draft';

        // Insert data reimburse ke database
        Reimburse::create($data);

        // Redirect dengan success message
        return redirect()->route('reimburse.index')->with('success', 'Pengajuan reimburse berhasil ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     * Update data reimburse (hanya jika status masih draft)
     */
    public function update(Request $request, Reimburse $reimburse)
    {
        // Cek apakah status masih draft (hanya draft yang bisa di-edit)
        if ($reimburse->status !== 'draft') {
            return redirect()->route('reimburse.index')->with('error', 'Reimburse yang sudah disetujui/ditolak tidak dapat diubah!');
        }

        // Update data reimburse
        $reimburse->update($request->all());

        // Redirect dengan success message
        return redirect()->route('reimburse.index')->with('success', 'Data reimburse berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     * Bulk delete reimburse yang dipilih
     */
    public function destroy(Request $request)
    {
        return $this->destroySelectedBy($request, Reimburse::class, 'ids', 'reimburse_code', 'reimburse.index');
    }

    /**
     * Approve reimburse (role super admin)
     * Menyetujui reimburse yang dipilih
     */
    public function approve(Request $request)
    {
        // Ambil array reimburse_code yang dipilih
        $ids = $request->input('ids');

        // Validasi: cek apakah ada data yang dipilih
        if (empty($ids)) {
            return redirect()->route('reimburse.index')->with('error', 'Tidak ada reimburse yang dipilih!');
        }

        // Update status menjadi approved untuk semua reimburse yang dipilih
        Reimburse::whereIn('reimburse_code', $ids)
            ->where('status', 'draft') // Hanya yang statusnya draft
            ->update([
                'status' => 'approved',
                'status_changed_at' => now(), // Waktu approval
            ]);

        return redirect()->route('reimburse.index')->with('success', 'Reimburse berhasil disetujui!');
    }

    /**
     * Reject reimburse (role super admin)
     * Menolak reimburse yang dipilih
     */
    public function reject(Request $request)
    {
        // Ambil array reimburse_code yang dipilih
        $ids = $request->input('ids');

        // Validasi: cek apakah ada data yang dipilih
        if (empty($ids)) {
            return redirect()->route('reimburse.index')->with('error', 'Tidak ada reimburse yang dipilih!');
        }

        // Update status menjadi rejected
        Reimburse::whereIn('reimburse_code', $ids)
            ->where('status', 'draft') // Hanya yang statusnya draft
            ->update([
                'status' => 'rejected',
                'status_changed_at' => now(), // Waktu rejection
            ]);

        return redirect()->route('reimburse.index')->with('success', 'Reimburse berhasil ditolak!');
    }

    /**
     * Export reimburse to PDF
     */
    public function exportPdf(Request $request)
    {
        // Ambil filter dari request
        $search = $request->input('search');
        $status = $request->input('status');

        // Query reimburse dengan filter yang sama seperti di index
        $reimburses = Reimburse::query()
            ->when($search, function ($query, $search) {
                return $query->where('project_name', 'like', "%{$search}%")
                    ->orWhere('reimburse_code', 'like', "%{$search}%");
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest('date')
            ->get();

        // Hitung total amount
        $totalAmount = $reimburses->sum('total_amount');

        // Load view PDF
        $pdf = Pdf::loadView('exports.finance.reimburse-pdf', compact('reimburses', 'totalAmount', 'status'));

        // Set paper size dan orientation
        $pdf->setPaper('a4', 'landscape');

        // Download PDF
        return $pdf->download('reimburse_' . date('YmdHis') . '.pdf');
    }

    /**
     * Export reimburse to Excel
     */
    public function exportExcel(Request $request)
    {
        // Ambil filter dari request
        $search = $request->input('search');
        $status = $request->input('status');

        // Query reimburse dengan filter
        $reimburses = Reimburse::query()
            ->when($search, function ($query, $search) {
                return $query->where('project_name', 'like', "%{$search}%")
                    ->orWhere('reimburse_code', 'like', "%{$search}%");
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest('date')
            ->get();

        // Export ke Excel
        return Excel::download(
            new ReimburseExport($reimburses, $status),
            'reimburse_' . date('YmdHis') . '.xlsx'
        );
    }

    /**
     * Get selected reimburses total (for super admin to see total before approving)
     * API endpoint untuk menghitung total dari reimburse yang dipilih
     */
    public function getSelectedTotal(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['total' => 0]);
        }

        // Hitung total amount dari reimburse yang dipilih
        $total = Reimburse::whereIn('reimburse_code', $ids)->sum('total_amount');

        return response()->json([
            'total' => $total,
            'formatted_total' => 'Rp ' . number_format($total, 0, ',', '.')
        ]);
    }
}
