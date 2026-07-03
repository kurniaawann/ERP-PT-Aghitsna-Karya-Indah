<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Report\ExpenseRecap;
use App\Models\Report\TransactionCategory;
use App\Exports\Report\ExpenseRecapExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\InputNormalizer;
use App\Traits\HasBulkActions;
use Illuminate\Support\Facades\Log;

class RecapExpenseController extends Controller
{
    use HasBulkActions;

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
        $expenseRecaps = ExpenseRecap::query()
            // Eager load relasi category, salesRecap, dan creator untuk efisiensi query (hindari N+1 problem)
            ->with(['category', 'salesRecap', 'creator'])

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

            // Urutkan hasil berdasarkan transaction_date ascending (tanggal paling lama di atas)
            ->orderBy('transaction_date', 'asc')

            // Jika tanggal sama, urutkan berdasarkan created_at ascending (data lama dulu)
            ->orderBy('created_at', 'asc')

            // Pagination 10 data per halaman dengan append query parameters agar filter tetap ada saat pindah halaman
            ->paginate(10)->appends($request->all());

        // Ambil semua kategori transaksi yang aktif untuk ditampilkan di dropdown form
        // Urutkan berdasarkan sort_order agar sesuai urutan yang diinginkan user
        $categories = TransactionCategory::active()->orderBy('sort_order')->get();

        // Hitung total income dan expense dengan filter yang sama seperti di atas
        $totals = ExpenseRecap::query()
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

        // Return view 'recap-expense' dengan data expenseRecaps, categories, dan totals
        return view('pages.finance.recap-expense', compact('expenseRecaps', 'categories', 'totals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Ambil semua data dari form (validasi sudah dilakukan di HTML)
        $data = $request->all();

        // Set income_amount ke null (karena ini expense, bukan income)
        $data['income_amount'] = null;
        $data['expense_amount'] = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);

        try {
            // Simpan data expense report ke database
            ExpenseRecap::create($data);

            // Redirect ke halaman index dengan pesan sukses
            return redirect()->route('recap-expense.index')
                ->with('success', 'Data rekap pengeluaran berhasil ditambahkan!');

        } catch (\Exception $e) {
            Log::error('Recap Expense store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Cari data expense report berdasarkan ID, jika tidak ditemukan akan throw 404
        $expenseRecap = ExpenseRecap::findOrFail($id);

        // Cek apakah data ini auto-generated dari sales report
        // Data auto-generated tidak boleh diubah secara manual untuk menjaga konsistensi
        if ($expenseRecap->isAutoGenerated()) {
            // Redirect kembali dengan pesan error
            return back()->with('error', 'Data yang auto-generated dari sales report tidak dapat diubah!');
        }

        // Ambil semua data dari form (validasi sudah dilakukan di HTML)
        $data = $request->all();
        $data['expense_amount'] = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);

        try {
            // Update data expense report dengan data dari form
            $expenseRecap->update($data);

            // Redirect ke halaman index dengan pesan sukses
            return redirect()->route('recap-expense.index')
                ->with('success', 'Data rekap pengeluaran berhasil diupdate!');

        } catch (\Exception $e) {
            Log::error('Recap Expense update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Terjadi kesalahan saat mengupdate data. Silakan coba lagi.');
        }
    }

    /**
     * Delete selected items (bulk delete).
     */
    public function destroySelected(Request $request)
    {
        // Ambil array ID yang dipilih dari input (default empty array jika tidak ada)
        $selectedIds = $request->input('selected_expenses', []);

        // Validasi sederhana: pastikan ada data yang dipilih
        if (empty($selectedIds)) {
            return back()->with('error', 'Tidak ada data yang dipilih!');
        }

        // Hapus semua expense report yang ID-nya ada dalam array $selectedIds
        // whereIn() akan match semua record dengan id di dalam array
        // delete() akan menghapus record tersebut dan return jumlah row yang terhapus
        return $this->destroySelectedBy($request, ExpenseRecap::class, 'selected_expenses', 'id', 'recap-expense.index');
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
        $expenseRecaps = ExpenseRecap::query()
            // Eager load relasi category dan salesRecap untuk ditampilkan di Excel
            ->with(['category', 'salesRecap',])

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

            // Urutkan berdasarkan tanggal transaksi ascending (tanggal paling lama di atas)
            ->orderBy('transaction_date', 'asc')

            // Jika tanggal sama, urutkan berdasarkan created_at ascending
            ->orderBy('created_at', 'asc')

            // Ambil semua data (tanpa pagination) untuk export
            ->get();

        // Hitung total income dan expense dari collection yang sudah di-filter
        $totals = (object) [
            // Sum semua income_amount dari collection
            'total_income' => $expenseRecaps->sum('income_amount'),

            // Sum semua expense_amount dari collection
            'total_expense' => $expenseRecaps->sum('expense_amount'),
        ];

        // Hitung balance (saldo): total income - total expense
        $totals->balance = $totals->total_income - $totals->total_expense;

        // Generate nama file dengan format: rekap-pengeluaran-YYYY-MM-DD-HHMMSS.xlsx
        // date('Y-m-d-His') menghasilkan format tahun-bulan-tanggal-jammenitdetik
        $filename = 'Rekap_Pengeluaran_' . date('Y-m-d') . '.xlsx';

        // Download Excel menggunakan ExpenseRecapExport class
        // Parameter: data, month, year, category (null), totals
        return Excel::download(
            new ExpenseRecapExport($expenseRecaps, $month, $year, null, $totals),
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
        $expenseRecaps = ExpenseRecap::query()
            // Eager load relasi category, salesRecap, dan creator untuk ditampilkan di PDF
            ->with(['category', 'salesRecap', 'creator'])

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

            // Urutkan berdasarkan tanggal transaksi ascending (tanggal paling lama di atas)
            ->orderBy('transaction_date', 'asc')

            // Jika tanggal sama, urutkan berdasarkan created_at ascending
            ->orderBy('created_at', 'asc')

            // Ambil semua data (tanpa pagination) untuk export PDF
            ->get();

        // Hitung total income dan expense dari collection yang sudah di-filter
        $totals = (object) [
            // Sum semua income_amount dari collection
            'total_income' => $expenseRecaps->sum('income_amount'),

            // Sum semua expense_amount dari collection
            'total_expense' => $expenseRecaps->sum('expense_amount'),
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
        $pdf = Pdf::loadView('exports.report.expense-report-pdf', [
            // Data expense recaps yang sudah di-filter
            'expenseRecaps' => $expenseRecaps,

            // Total income, expense, dan balance
            'totals' => $totals,

            // Judul periode untuk header PDF
            'periodTitle' => $periodTitle,
        ])
            // Set ukuran kertas A4 dengan orientasi landscape (horizontal)
            ->setPaper('a4', 'landscape');

        // Generate nama file dengan format: rekap-pengeluaran-YYYY-MM-DD-HHMMSS.pdf
        $filename = 'Rekap_Pengeluaran_' . date('Y-m-d') . '.pdf';

        // Download PDF dengan nama file yang sudah di-generate
        return $pdf->download($filename);
    }
}

