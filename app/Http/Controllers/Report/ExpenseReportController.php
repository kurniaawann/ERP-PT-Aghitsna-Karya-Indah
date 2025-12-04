<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Report\ExpenseReport;
use App\Models\Report\TransactionCategory;
use App\Exports\Report\ExpenseReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ExpenseReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil parameter filter bulan dari request (untuk filter berdasarkan bulan)
        $month = $request->get('month');

        // Ambil parameter filter tahun dari request (untuk filter berdasarkan tahun)
        $year = $request->get('year');

        // Ambil parameter filter kategori dari request (untuk filter berdasarkan kategori transaksi)
        $category = $request->get('category');

        // Ambil parameter pencarian dari request (untuk search invoice_number, description, money_source)
        $search = $request->get('search');

        // Mulai query untuk mengambil data expense reports
        $expenseReports = ExpenseReport::query()
            // Eager load relasi category, salesReport, dan creator untuk efisiensi query (hindari N+1 problem)
            ->with(['category', 'salesReport', 'creator'])

            // Filter berdasarkan bulan jika parameter $month ada
            ->when($month, function ($query, $month) {
                // Gunakan whereMonth untuk filter berdasarkan bulan dari field transaction_date
                return $query->whereMonth('transaction_date', $month);
            })

            // Filter berdasarkan tahun jika parameter $year ada
            ->when($year, function ($query, $year) {
                // Gunakan whereYear untuk filter berdasarkan tahun dari field transaction_date
                return $query->whereYear('transaction_date', $year);
            })

            // Filter berdasarkan kategori jika parameter $category ada
            ->when($category, function ($query, $category) {
                // Filter where transaction_category_id sama dengan $category yang dipilih
                return $query->where('transaction_category_id', $category);
            })

            // Filter berdasarkan pencarian jika parameter $search ada
            ->when($search, function ($query, $search) {
                // Gunakan where dengan closure untuk grouping kondisi OR
                return $query->where(function ($q) use ($search) {
                    // Cari di kolom invoice_number dengan LIKE (partial match)
                    $q->where('invoice_number', 'like', "%{$search}%")
                        // ATAU cari di kolom description dengan LIKE (partial match)
                        ->orWhere('description', 'like', "%{$search}%")
                        // ATAU cari di kolom money_source dengan LIKE (partial match)
                        ->orWhere('money_source', 'like', "%{$search}%");
                });
            })

            // Urutkan hasil berdasarkan transaction_date descending (terbaru di atas)
            ->orderBy('transaction_date', 'desc')

            // Jika tanggal sama, urutkan berdasarkan created_at descending (yang dibuat terakhir di atas)
            ->orderBy('created_at', 'desc')

            // Pagination 10 data per halaman
            ->paginate(10);

        // Ambil semua kategori transaksi yang aktif untuk ditampilkan di dropdown form
        // Urutkan berdasarkan sort_order agar sesuai urutan yang diinginkan user
        $categories = TransactionCategory::active()->orderBy('sort_order')->get();

        // Hitung total income dan expense dengan filter yang sama seperti di atas
        $totals = ExpenseReport::query()
            // Filter berdasarkan bulan jika ada
            ->when($month, function ($query, $month) {
                return $query->whereMonth('transaction_date', $month);
            })

            // Filter berdasarkan tahun jika ada
            ->when($year, function ($query, $year) {
                return $query->whereYear('transaction_date', $year);
            })

            // Filter berdasarkan kategori jika ada
            ->when($category, function ($query, $category) {
                return $query->where('transaction_category_id', $category);
            })

            // Filter berdasarkan pencarian jika ada
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('money_source', 'like', "%{$search}%");
                });
            })

            // Select dengan agregasi SUM untuk menghitung total
            ->select(
                // Hitung total income_amount dan beri alias 'total_income'
                DB::raw('SUM(income_amount) as total_income'),

                // Hitung total expense_amount dan beri alias 'total_expense'
                DB::raw('SUM(expense_amount) as total_expense')
            )
            // Ambil hasil pertama (karena hasil agregasi hanya 1 row)
            ->first();

        // Hitung balance (saldo): total income dikurangi total expense
        // Gunakan null coalescing operator (??) untuk handle jika null, default ke 0
        $totals->balance = ($totals->total_income ?? 0) - ($totals->total_expense ?? 0);

        // Return view 'expense-report' dengan data expenseReports, categories, dan totals
        return view('pages.report.expense-report', compact('expenseReports', 'categories', 'totals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Buat validator untuk validasi input dari form
        $validator = Validator::make($request->all(), [
            // Kategori transaksi wajib diisi dan harus ada di tabel transaction_categories
            'transaction_category_id' => 'required|exists:transaction_categories,id',

            // Tanggal transaksi wajib diisi dan harus format date yang valid
            'transaction_date' => 'required|date',

            // Deskripsi/keterangan wajib diisi, maksimal 1000 karakter
            'description' => 'required|string|max:1000',

            // Jumlah pengeluaran wajib diisi, harus integer, dan tidak boleh negatif
            'expense_amount' => 'required|integer|min:0',

            // Nomor invoice boleh kosong, maksimal 100 karakter
            'invoice_number' => 'nullable|string|max:100',

            // Sumber uang boleh kosong, maksimal 255 karakter
            'money_source' => 'nullable|string|max:255',

            // Catatan tambahan boleh kosong
            'notes' => 'nullable|string',
        ], [
            // Custom error message untuk kategori
            'transaction_category_id.required' => 'Kategori pengeluaran wajib dipilih',

            // Custom error message untuk tanggal
            'transaction_date.required' => 'Tanggal wajib diisi',

            // Custom error message untuk deskripsi
            'description.required' => 'Keterangan wajib diisi',

            // Custom error message untuk jumlah pengeluaran (required)
            'expense_amount.required' => 'Jumlah pengeluaran wajib diisi',

            // Custom error message untuk jumlah pengeluaran (harus integer)
            'expense_amount.integer' => 'Jumlah pengeluaran harus berupa angka',

            // Custom error message untuk jumlah pengeluaran (tidak boleh negatif)
            'expense_amount.min' => 'Jumlah pengeluaran tidak boleh negatif',
        ]);

        // Jika validasi gagal, redirect kembali dengan error dan input sebelumnya
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Ambil data yang sudah divalidasi
        $validated = $validator->validated();

        // Tambahkan field created_by dengan ID user yang sedang login (untuk audit trail)
        $validated['created_by'] = Auth::id();

        // Set income_amount ke null (karena ini expense, bukan income)
        $validated['income_amount'] = null;

        try {
            // Simpan data expense report ke database
            ExpenseReport::create($validated);

            // Redirect ke halaman index dengan pesan sukses
            return redirect()->route('expense-report.index')
                ->with('success', 'Data laporan pengeluaran berhasil ditambahkan!');

        } catch (\Exception $e) {
            // Jika terjadi error saat menyimpan, redirect kembali dengan pesan error dan input sebelumnya
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Cari data expense report berdasarkan ID, jika tidak ditemukan akan throw 404
        $expenseReport = ExpenseReport::findOrFail($id);

        // Cek apakah data ini auto-generated dari sales report
        // Data auto-generated tidak boleh diubah secara manual untuk menjaga konsistensi
        if ($expenseReport->isAutoGenerated()) {
            // Redirect kembali dengan pesan error
            return back()->with('error', 'Data yang auto-generated dari sales report tidak dapat diubah!');
        }

        // Buat validator untuk validasi input dari form edit
        $validator = Validator::make($request->all(), [
            // Kategori transaksi wajib diisi dan harus ada di tabel transaction_categories
            'transaction_category_id' => 'required|exists:transaction_categories,id',

            // Tanggal transaksi wajib diisi dan harus format date yang valid
            'transaction_date' => 'required|date',

            // Deskripsi/keterangan wajib diisi, maksimal 1000 karakter
            'description' => 'required|string|max:1000',

            // Jumlah pengeluaran wajib diisi, harus integer, dan tidak boleh negatif
            'expense_amount' => 'required|integer|min:0',

            // Nomor invoice boleh kosong, maksimal 100 karakter
            'invoice_number' => 'nullable|string|max:100',

            // Sumber uang boleh kosong, maksimal 255 karakter
            'money_source' => 'nullable|string|max:255',

            // Catatan tambahan boleh kosong
            'notes' => 'nullable|string',
        ], [
            // Custom error message untuk kategori
            'transaction_category_id.required' => 'Kategori pengeluaran wajib dipilih',

            // Custom error message untuk tanggal
            'transaction_date.required' => 'Tanggal wajib diisi',

            // Custom error message untuk deskripsi
            'description.required' => 'Keterangan wajib diisi',

            // Custom error message untuk jumlah pengeluaran
            'expense_amount.required' => 'Jumlah pengeluaran wajib diisi',
        ]);

        // Jika validasi gagal, redirect kembali dengan error dan input sebelumnya
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Ambil data yang sudah divalidasi
        $validated = $validator->validated();

        try {
            // Update data expense report dengan data yang sudah divalidasi
            $expenseReport->update($validated);

            // Redirect ke halaman index dengan pesan sukses
            return redirect()->route('expense-report.index')
                ->with('success', 'Data laporan pengeluaran berhasil diupdate!');

        } catch (\Exception $e) {
            // Jika terjadi error saat update, redirect kembali dengan pesan error
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Delete selected items (bulk delete).
     */
    public function destroySelected(Request $request)
    {
        // Buat validator untuk validasi input dari checkbox selection
        $validator = Validator::make($request->all(), [
            // Field selected_expenses wajib ada, harus array, minimal 1 item
            'selected_expenses' => 'required|array|min:1',

            // Setiap item dalam array harus exist di tabel expense_reports kolom id
            'selected_expenses.*' => 'exists:expense_reports,id',
        ], [
            // Custom error message jika tidak ada data yang dipilih
            'selected_expenses.required' => 'Tidak ada data yang dipilih!',

            // Custom error message jika minimal tidak terpenuhi
            'selected_expenses.min' => 'Pilih minimal satu data untuk dihapus!',

            // Custom error message jika ID tidak valid
            'selected_expenses.*.exists' => 'Data yang dipilih tidak valid!',
        ]);

        // Jika validasi gagal, redirect kembali dengan error
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Ambil array ID yang dipilih dari input (default empty array jika tidak ada)
        $selectedIds = $request->input('selected_expenses', []);

        // Hapus semua expense report yang ID-nya ada dalam array $selectedIds
        // whereIn() akan match semua record dengan id di dalam array
        // delete() akan menghapus record tersebut dan return jumlah row yang terhapus
        $deletedCount = ExpenseReport::whereIn('id', $selectedIds)->delete();

        // Redirect ke halaman index dengan pesan sukses dan jumlah data yang terhapus
        return redirect()->route('expense-report.index')
            ->with('success', "Berhasil menghapus {$deletedCount} data pengeluaran.");
    }

    /**
     * Export expense report to Excel
     */
    public function exportExcel(Request $request)
    {
        // Ambil parameter filter bulan dari request
        $month = $request->get('month');

        // Ambil parameter filter tahun dari request
        $year = $request->get('year');

        // Query data expense reports dengan filter (hanya bulan dan tahun, tanpa kategori dan search)
        $expenseReports = ExpenseReport::query()
            // Eager load relasi category dan salesReport untuk ditampilkan di Excel
            ->with(['category', 'salesReport',])

            // Filter berdasarkan bulan jika parameter ada
            ->when($month, function ($query, $month) {
                // Gunakan whereMonth untuk filter bulan dari transaction_date
                return $query->whereMonth('transaction_date', $month);
            })

            // Filter berdasarkan tahun jika parameter ada
            ->when($year, function ($query, $year) {
                // Gunakan whereYear untuk filter tahun dari transaction_date
                return $query->whereYear('transaction_date', $year);
            })

            // Urutkan berdasarkan tanggal transaksi descending (terbaru di atas)
            ->orderBy('transaction_date', 'desc')

            // Jika tanggal sama, urutkan berdasarkan created_at descending
            ->orderBy('created_at', 'desc')

            // Ambil semua data (tanpa pagination) untuk export
            ->get();

        // Hitung total income dan expense dari collection yang sudah di-filter
        $totals = (object) [
            // Sum semua income_amount dari collection
            'total_income' => $expenseReports->sum('income_amount'),

            // Sum semua expense_amount dari collection
            'total_expense' => $expenseReports->sum('expense_amount'),
        ];

        // Hitung balance (saldo): total income - total expense
        $totals->balance = $totals->total_income - $totals->total_expense;

        // Generate nama file dengan format: laporan-pengeluaran-YYYY-MM-DD-HHMMSS.xlsx
        // date('Y-m-d-His') menghasilkan format tahun-bulan-tanggal-jammenitdetik
        $filename = 'laporan-pengeluaran-' . date('Y-m-d-His') . '.xlsx';

        // Download Excel menggunakan ExpenseReportExport class
        // Parameter: data, month, year, category (null), totals
        return Excel::download(
            new ExpenseReportExport($expenseReports, $month, $year, null, $totals),
            $filename
        );
    }

    /**
     * Export expense report to PDF
     */
    public function exportPdf(Request $request)
    {
        // Ambil parameter filter bulan dari request
        $month = $request->get('month');

        // Ambil parameter filter tahun dari request
        $year = $request->get('year');

        // Query data expense reports dengan filter (hanya bulan dan tahun)
        $expenseReports = ExpenseReport::query()
            // Eager load relasi category, salesReport, dan creator untuk ditampilkan di PDF
            ->with(['category', 'salesReport', 'creator'])

            // Filter berdasarkan bulan jika parameter ada
            ->when($month, function ($query, $month) {
                // Gunakan whereMonth untuk filter bulan dari transaction_date
                return $query->whereMonth('transaction_date', $month);
            })

            // Filter berdasarkan tahun jika parameter ada
            ->when($year, function ($query, $year) {
                // Gunakan whereYear untuk filter tahun dari transaction_date
                return $query->whereYear('transaction_date', $year);
            })

            // Urutkan berdasarkan tanggal transaksi descending (terbaru di atas)
            ->orderBy('transaction_date', 'desc')

            // Jika tanggal sama, urutkan berdasarkan created_at descending
            ->orderBy('created_at', 'desc')

            // Ambil semua data (tanpa pagination) untuk export PDF
            ->get();

        // Hitung total income dan expense dari collection yang sudah di-filter
        $totals = (object) [
            // Sum semua income_amount dari collection
            'total_income' => $expenseReports->sum('income_amount'),

            // Sum semua expense_amount dari collection
            'total_expense' => $expenseReports->sum('expense_amount'),
        ];

        // Hitung balance (saldo): total income - total expense
        $totals->balance = $totals->total_income - $totals->total_expense;

        // Build judul periode untuk ditampilkan di header PDF
        // Inisialisasi array kosong untuk menampung bagian-bagian periode
        $periodParts = [];

        // Jika ada filter bulan DAN tahun
        if ($month && $year) {
            // Create Carbon instance untuk bulan tersebut (hari diset ke 1)
            // locale('id') untuk bahasa Indonesia
            // translatedFormat('F') untuk nama bulan penuh (misal: Januari, Februari)
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');

            // Tambahkan ke array dengan format "Januari 2025"
            $periodParts[] = $monthName . ' ' . $year;

        } elseif ($month) {
            // Jika hanya ada filter bulan (tanpa tahun)
            // Ambil nama bulan dalam bahasa Indonesia
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');

            // Tambahkan ke array dengan prefix "Bulan"
            $periodParts[] = 'Bulan ' . $monthName;

        } elseif ($year) {
            // Jika hanya ada filter tahun (tanpa bulan)
            // Tambahkan ke array dengan prefix "Tahun"
            $periodParts[] = 'Tahun ' . $year;
        }

        // Gabungkan semua bagian periode dengan separator " - "
        // Jika tidak ada filter, tampilkan "Semua Periode"
        $periodTitle = !empty($periodParts) ? implode(' - ', $periodParts) : 'Semua Periode';

        // Generate PDF dari view 'expense-report-pdf' dengan data yang diperlukan
        $pdf = Pdf::loadView('exports.expense-report-pdf', [
            // Data expense reports yang sudah di-filter
            'expenseReports' => $expenseReports,

            // Total income, expense, dan balance
            'totals' => $totals,

            // Judul periode untuk header PDF
            'periodTitle' => $periodTitle,
        ])
            // Set ukuran kertas A4 dengan orientasi landscape (horizontal)
            ->setPaper('a4', 'landscape');

        // Generate nama file dengan format: laporan-pengeluaran-YYYY-MM-DD-HHMMSS.pdf
        $filename = 'laporan-pengeluaran-' . date('Y-m-d-His') . '.pdf';

        // Download PDF dengan nama file yang sudah di-generate
        return $pdf->download($filename);
    }
}
