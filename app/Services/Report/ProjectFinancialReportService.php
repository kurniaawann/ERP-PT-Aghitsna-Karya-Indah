<?php

namespace App\Services\Report;

use App\Models\Finance\PaymentProof;
use App\Models\Finance\ProjectRecap;
use App\Models\Report\ProjectFinancialReport;
use App\Models\Report\ProjectFinancialReportItem;
use App\Models\Report\TransactionCategory;
use App\Models\Sdm\KasbonPayment;
use App\Models\Sdm\Payroll;
use App\Services\InputNormalizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

/**
 * Service layer untuk operasi bisnis Laporan Keuangan Proyek.
 *
 * Menangani semua logika bisnis terkait laporan keuangan proyek:
 * - Pembuatan laporan otomatis (1 rekap proyek = 1 laporan, auto-create)
 * - Pembuatan ID unik (race-condition safe)
 * - CRUD item "Bon" termasuk upload bukti pembayaran
 * - Perhitungan grand totals (income, expense, balance)
 * - Bulk delete
 */
class ProjectFinancialReportService
{
    /**
     * Direktori penyimpanan bukti pembayaran pada disk public.
     */
    private const PROOF_DIRECTORY = 'project-financial-reports';

    /**
     * Membangun atau mengambil laporan keuangan proyek untuk sebuah rekap.
     *
     * Laporan dibuat otomatis saat tombol "Laporan Keuangan" pada tabel
     * Rekap Proyek diklik pertama kali (1 rekap = 1 laporan). Menggunakan
     * unique constraint project_recap_id untuk mencegah duplikat saat
     * bersamaan (race condition).
     */
    public function getOrCreateForRecap(ProjectRecap $recap): ProjectFinancialReport
    {
        $report = $recap->financialReport;

        if ($report) {
            return $report;
        }

        try {
            return ProjectFinancialReport::create([
                'project_recap_id' => $recap->id,
                'created_by' => auth()->id(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            // Duplicate entry: laporan sudah dibuat oleh request lain.
            $report = ProjectFinancialReport::where('project_recap_id', $recap->id)->first();

            if (! $report) {
                throw $e;
            }

            return $report;
        }
    }

    /**
     * Ambil semua item laporan yang urut berdasarkan kategori lalu tanggal.
     *
     * Urutan tampil mengikuti kategori (sort_order), kemudian tanggal naik,
     * kemudian id sebagai tie-breaker agar deterministik.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Report\ProjectFinancialReportItem>
     */
    public function getItems(ProjectFinancialReport $report): Collection
    {
        return $report->items()
            ->with(['category'])
            ->orderByRaw('(SELECT sort_order FROM transaction_categories WHERE transaction_categories.id = project_financial_report_items.transaction_category_id)')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Hitung grand totals (total income, total expense, balance).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Report\ProjectFinancialReportItem>  $items
     * @return object Object berisi total_income, total_expense, balance
     */
    public function getGrandTotals($items): object
    {
        // Baris informasi (kasbon personal) tidak memengaruhi total laporan.
        $financialItems = $items->where('is_informational', false);
        $totalIncome = (int) $financialItems->sum('income_amount');
        $totalExpense = (int) $financialItems->sum('expense_amount');

        return (object) [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * Membuat item "Bon" baru pada laporan keuangan proyek.
     *
     * Logika INCOME vs EXPENSE (ditentukan dari tipe kategori transaksi):
     * - Kategori INCOME: expense_amount dipindah ke income_amount.
     * - Kategori selain INCOME (EXPENSE): income_amount null, expense_amount dipakai.
     *
     * @param  array<string, mixed>  $data  Data yang sudah validasi dari FormRequest
     * @param  \Illuminate\Http\UploadedFile|null  $proofFile  Bukti pembayaran (opsional)
     */
    public function createItem(ProjectFinancialReport $report, array $data, ?UploadedFile $proofFile): ProjectFinancialReportItem
    {
        $data['project_financial_report_id'] = $report->id;

        $data = $this->resolveAmounts($data);

        if ($proofFile) {
            $stored = $this->storeProofFile($proofFile);
            $data['proof_file'] = $stored['file_path'];
            $data['proof_file_name'] = $stored['file_name'];
        }

        $data['created_by'] = auth()->id();

        try {
            $item = ProjectFinancialReportItem::create($data);
            $this->flushUsedCategoryCache(auth()->id());

            return $item;
        } catch (\Throwable $throwable) {
            if (isset($data['proof_file'])) {
                $this->deleteProofFile($data['proof_file']);
            }
            throw $throwable;
        }
    }

    /**
     * Membuat banyak item "Bon" sekaligus pada laporan keuangan proyek.
     *
     * Dipakai oleh form tambah dengan struktur dinamis (beberapa transaksi
     * dalam satu submit). Setiap entri diproses oleh createItem() sehingga
     * logika INCOME vs EXPENSE dan upload bukti tetap terpusat.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, \Illuminate\Http\UploadedFile|null>>  $proofFiles
     * @return array<int, \App\Models\Report\ProjectFinancialReportItem>
     */
    public function createItems(ProjectFinancialReport $report, array $items, array $proofFiles): array
    {
        $created = [];

        foreach ($items as $index => $itemData) {
            $proofFile = $proofFiles[$index]['proof_file'] ?? null;
            $created[] = $this->createItem($report, $itemData, $proofFile);
        }

        return $created;
    }

    /**
     * Ambil (atau buat otomatis) kategori UANG_MASUK milik user.
     *
     * Kategori dicari berdasarkan user yang memicu (created_by), bukan global,
     * karena daftar kategori transaksi ditampilkan per-user. Bila user belum
     * punya kategori "uang masuk" (modul project_finance, tipe INCOME, kode
     * diawali UANG_MASUK), dibuat otomatis satu kali saja. Karena kolom `code`
     * unik global, bila kode UANG_MASUK sudah terpakai (oleh user lain), kode
     * di-increment menjadi UANG_MASUK_1, UANG_MASUK_2, dst. sampai ketemu
     * yang kosong.
     *
     * @param  int|string|null  $userId  Default: user yang sedang login.
     */
    public function resolveIncomeCategory($userId = null): ?TransactionCategory
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return null;
        }

        $incomeCategory = TransactionCategory::where('created_by', $userId)
            ->module(TransactionCategory::MODULE_PROJECT_FINANCE)
            ->where('type', TransactionCategory::TYPE_INCOME)
            ->where('code', 'LIKE', 'UANG_MASUK%')
            ->orderBy('id')
            ->first();

        if ($incomeCategory) {
            return $incomeCategory;
        }

        $baseCode = 'UANG_MASUK';
        $suffix = 1;

        while (true) {
            $code = $suffix === 1 ? $baseCode : $baseCode.'_'.$suffix;
            $suffix++;

            if (TransactionCategory::where('code', $code)->exists()) {
                continue;
            }

            try {
                $incomeCategory = TransactionCategory::create([
                    'name' => 'UANG MASUK',
                    'code' => $code,
                    'type' => TransactionCategory::TYPE_INCOME,
                    'module' => TransactionCategory::MODULE_PROJECT_FINANCE,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);
                break;
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        $this->flushCategoryCache($userId);
        app(TransactionCategoryService::class)->flushCache($userId);

        return $incomeCategory;
    }

    /**
     * Sinkronkan item Laporan Keuangan Proyek dari bukti pembayaran (recap).
     *
     * Setiap bukti pembayaran dengan invoice_type 'recap' otomatis menjadi
     * satu baris "Uang Masuk" pada laporan keuangan proyek rekap terkait:
     * - Keterangan: "Pembayaran ke {stage} proyek {nama}"
     * - Nominal: amount bukti pembayaran → income_amount
     * - Tanggal: payment_date bukti pembayaran
     * - Bukti: file bukti pembayaran (dipakai ulang, tidak disalin)
     *
     * Dipanggil dari observer PaymentProof saat bukti dibuat/diubah. Idempotent:
     * jika item dengan payment_proof_id sudah ada, diperbarui; bila tidak, dibuat.
     * Bila invoice_type bukan 'recap' (bukti dipindah ke invoice lain), item
     * terkait dihapus.
     */
    public function syncFromPaymentProof(PaymentProof $proof): ?ProjectFinancialReportItem
    {
        if ($proof->invoice_type !== 'recap') {
            $this->deleteFromPaymentProof($proof);

            return null;
        }

        $recap = ProjectRecap::where('id', $proof->invoice_number)->first();

        if (! $recap) {
            return null;
        }

        $incomeCategory = $this->resolveIncomeCategory($proof->created_by);

        if (! $incomeCategory) {
            return null;
        }

        $report = $this->getOrCreateForRecap($recap);

        $item = ProjectFinancialReportItem::where('payment_proof_id', $proof->id)->first();

        $recapName = trim((string) $recap->project_name);
        $recapName = preg_replace('/^proyek\s+/i', '', $recapName) ?: $recapName;

        $stage = (int) ($proof->payment_stage ?? 1);
        $data = [
            'transaction_category_id' => $incomeCategory->id,
            'transaction_date' => $proof->payment_date?->toDateString() ?? now()->toDateString(),
            'description' => 'Pembayaran ke '.$stage.' proyek '.$recapName,
            'income_amount' => (int) $proof->amount,
            'expense_amount' => null,
            'proof_file' => $proof->file_path,
            'proof_file_name' => $proof->file_name,
        ];

        if ($item) {
            $item->update($data);
            $this->flushUsedCategoryCache($proof->created_by ?? auth()->id());

            return $item;
        }

        $created = ProjectFinancialReportItem::create(array_merge($data, [
            'project_financial_report_id' => $report->id,
            'payment_proof_id' => $proof->id,
            'created_by' => $proof->created_by ?? auth()->id(),
        ]));

        $this->flushUsedCategoryCache($proof->created_by ?? auth()->id());

        return $created;
    }

    /**
     * Ambil (atau buat otomatis) kategori pengeluaran "Upah Pekerja" milik user.
     *
     * Pola sama dengan resolveIncomeCategory(): kategori dicari per user dan
     * dibuat otomatis satu kali bila belum ada. Karena kolom `code` unik
     * global, kode dasar UPAH_PEKERJA di-increment menjadi UPAH_PEKERJA_1,
     * UPAH_PEKERJA_2, dst. bila sudah terpakai (misal oleh user lain).
     *
     * @param  int|string|null  $userId  Default: user yang sedang login.
     */
    public function resolveUpahPekerjaCategory($userId = null): ?TransactionCategory
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return null;
        }

        $expenseCategory = TransactionCategory::where('created_by', $userId)
            ->module(TransactionCategory::MODULE_PROJECT_FINANCE)
            ->where('type', TransactionCategory::TYPE_EXPENSE)
            ->where('code', 'LIKE', 'UPAH_PEKERJA%')
            ->orderBy('id')
            ->first();

        if ($expenseCategory) {
            return $expenseCategory;
        }

        $baseCode = 'UPAH_PEKERJA';
        $suffix = 1;

        while (true) {
            $code = $suffix === 1 ? $baseCode : $baseCode.'_'.$suffix;
            $suffix++;

            if (TransactionCategory::where('code', $code)->exists()) {
                continue;
            }

            try {
                $expenseCategory = TransactionCategory::create([
                    'name' => 'Upah Pekerja',
                    'code' => $code,
                    'type' => TransactionCategory::TYPE_EXPENSE,
                    'module' => TransactionCategory::MODULE_PROJECT_FINANCE,
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);
                break;
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        $this->flushCategoryCache($userId);
        app(TransactionCategoryService::class)->flushCache($userId);

        return $expenseCategory;
    }

    /**
     * Membuat atau memperbarui satu baris "Upah Kerja" pada laporan keuangan
     * proyek dari kumpulan payroll yang dibayar pada proyek + periode sama.
     *
     * Tidak dibuat per karyawan: seluruh payroll paid pada proyek + periode
     * yang sama diagregasi menjadi satu baris pengeluaran:
     * - Kategori: "Upah Pekerja" (per-user, module project_finance)
     * - Nominal: total net_salary seluruh payroll (expense_amount)
     * - Tanggal: payment_date terbaru di antara payroll (fallback: hari ini)
     * - Keterangan: "Upah Kerja periode dd/mm/yyyy - dd/mm/yyyy"
     *
     * Bila baris untuk proyek + periode tersebut sudah ada (misal sisa
     * payroll batch sebelumnya dibayar belakangan), nominalnya diperbarui
     * (aggregate) dan bukan membuat baris baru.
     *
     * Laporan keuangan dibuat otomatis bila rekap proyek belum memilikinya
     * (getOrCreateForRecap). Pemanggil wajib memastikan rekap proyek dengan
     * nama proyek payroll sudah ada; bila tidak, method ini tidak dipanggil.
     *
     * @param  ProjectRecap  $recap
     * @param  \Illuminate\Support\Collection<int, Payroll>  $payrolls  Payroll paid satu proyek + periode
     */
    public function upsertPayrollExpenseItem(ProjectRecap $recap, SupportCollection $payrolls): ?ProjectFinancialReportItem
    {
        if ($payrolls->isEmpty()) {
            return null;
        }

        $payrolls = $payrolls->values();
        $first = $payrolls->first();

        $category = $this->resolveUpahPekerjaCategory($first->created_by);

        if (! $category) {
            return null;
        }

        $report = $this->getOrCreateForRecap($recap);

        $periodStart = Carbon::parse($first->period_start_date)->format('d/m/Y');
        $periodEnd = $first->period_end_date
            ? Carbon::parse($first->period_end_date)->format('d/m/Y')
            : null;

        $description = 'Upah Kerja periode '.$periodStart.($periodEnd ? ' - '.$periodEnd : '');

        // Total dihitung dari SELURUH payroll paid pada proyek + periode yang
        // sama (bukan hanya batch ini) agar beberapa batch pembayaran untuk
        // periode sama tetap teragregasi ke satu baris.
        $periodStartDate = Carbon::parse($first->period_start_date)->format('Y-m-d');
        $periodEndDate = $first->period_end_date
            ? Carbon::parse($first->period_end_date)->format('Y-m-d')
            : null;

        $paidQuery = Payroll::where('created_by', $first->created_by)
            ->where('project_name', $first->project_name)
            ->where('status', 'paid')
            ->where('period_start_date', $periodStartDate);

        if ($periodEndDate) {
            $paidQuery->where('period_end_date', $periodEndDate);
        } else {
            $paidQuery->whereNull('period_end_date');
        }

        $total = (int) $paidQuery->sum('net_salary');
        $transactionDate = $paidQuery->max('payment_date') ?: now()->toDateString();

        $item = $report->items()
            ->where('transaction_category_id', $category->id)
            ->where('description', $description)
            ->first();

        $data = [
            'transaction_category_id' => $category->id,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'expense_amount' => $total,
        ];

        if ($item) {
            $item->update($data);
            $this->flushUsedCategoryCache($first->created_by ?? auth()->id());

            return $item;
        }

        return $this->createItem($report, $data, null);
    }

    /**
     * Menyesuaikan baris "Upah Kerja" pada laporan keuangan setelah sebagian
     * payroll paid pada proyek + periode dihapus.
     *
     * Baris agregat diperbarui ke total net_salary payroll paid yang tersisa;
     * bila tidak ada lagi payroll paid untuk proyek + periode tersebut, baris
     * laporan ikut dihapus agar tidak menjadi pengeluaran yatim.
     *
     * @param  string  $projectName
     * @param  string  $periodStart  Tanggal mulai periode (Y-m-d)
     * @param  string|null  $periodEnd  Tanggal akhir periode (Y-m-d)
     * @param  int|string|null  $userId
     */
    public function reconcilePayrollExpenseItem(string $projectName, string $periodStart, ?string $periodEnd, $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return;
        }

        $recap = ProjectRecap::whereRaw('LOWER(project_name) = ?', [mb_strtolower(trim($projectName))])->first();

        if (! $recap) {
            return;
        }

        $report = ProjectFinancialReport::where('project_recap_id', $recap->id)->first();

        if (! $report) {
            return;
        }

        $category = $this->resolveUpahPekerjaCategory($userId);

        if (! $category) {
            return;
        }

        $description = 'Upah Kerja periode '.Carbon::parse($periodStart)->format('d/m/Y')
            .($periodEnd ? ' - '.Carbon::parse($periodEnd)->format('d/m/Y') : '');

        $remainingQuery = Payroll::where('created_by', $userId)
            ->where('project_name', $projectName)
            ->where('status', 'paid')
            ->where('period_start_date', $periodStart);

        if ($periodEnd) {
            $remainingQuery->where('period_end_date', $periodEnd);
        } else {
            $remainingQuery->whereNull('period_end_date');
        }

        $remainingTotal = (int) $remainingQuery->sum('net_salary');

        $item = $report->items()
            ->where('transaction_category_id', $category->id)
            ->where('description', $description)
            ->first();

        if ($remainingTotal <= 0) {
            if ($item) {
                $item->delete();
            }
        } elseif ($item) {
            $item->update(['expense_amount' => $remainingTotal]);
        }

        $this->flushUsedCategoryCache($userId);
    }

    /**
     * Ambil (atau buat otomatis) kategori pengeluaran "Kasbon Pekerja" milik user.
     *
     * Pola sama dengan resolveUpahPekerjaCategory(): kategori dicari per user dan
     * dibuat otomatis satu kali bila belum ada. Karena kolom `code` unik global,
     * kode dasar KASBON di-increment menjadi KASBON_1, KASBON_2, dst. bila sudah
     * terpakai (misal oleh user lain).
     *
     * @param  int|string|null  $userId  Default: user yang sedang login.
     */
    public function resolveKasbonCategory($userId = null): ?TransactionCategory
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return null;
        }

        $kasbonCategory = TransactionCategory::where('created_by', $userId)
            ->module(TransactionCategory::MODULE_PROJECT_FINANCE)
            ->where('type', TransactionCategory::TYPE_EXPENSE)
            ->where('code', 'LIKE', 'KASBON%')
            ->orderBy('id')
            ->first();

        if ($kasbonCategory) {
            return $kasbonCategory;
        }

        $baseCode = 'KASBON';
        $suffix = 1;

        while (true) {
            $code = $suffix === 1 ? $baseCode : $baseCode.'_'.$suffix;
            $suffix++;

            if (TransactionCategory::where('code', $code)->exists()) {
                continue;
            }

            try {
                $kasbonCategory = TransactionCategory::create([
                    'name' => 'Kasbon Pekerja',
                    'code' => $code,
                    'type' => TransactionCategory::TYPE_EXPENSE,
                    'module' => TransactionCategory::MODULE_PROJECT_FINANCE,
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);
                break;
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        $this->flushCategoryCache($userId);
        app(TransactionCategoryService::class)->flushCache($userId);

        return $kasbonCategory;
    }

    /**
     * Membuat/memperbarui satu baris pengeluaran "Kasbon Divisi" pada Laporan
     * Keuangan Proyek ketika kasbon divisi ber-proyek dilunasi otomatis saat
     * payroll proyek tersebut dibayar.
     *
     * Baris dicatat pada kategori "Upah Pekerja" (sama dengan baris agregat
     * Upah Kerja payroll paid) — bukan kategori kasbon terpisah.
     *
     * Baris diagregasi per (divisi, periode kasbon) — beberapa kasbon divisi
     * dengan divisi + periode sama dijumlahkan ke satu baris. Keterangan memuat
     * rentang periode agar dua periode yang berbeda tidak saling menumpuk.
     *
     * @param  ProjectRecap  $recap
     * @param  string  $division        Nama divisi
     * @param  \Carbon\Carbon|string  $periodStart  Periode kasbon (mulai)
     * @param  \Carbon\Carbon|string|null  $periodEnd  Periode kasbon (akhir)
     * @param  int  $amount            Nominal yang dilunasi
     * @param  \Carbon\Carbon|string|null  $date  Tanggal transaksi (payment date)
     */
    public function upsertTeamKasbonExpenseItem(
        ProjectRecap $recap,
        string $division,
        $periodStart,
        $periodEnd,
        int $amount,
        $date = null,
        $userId = null
    ): ?ProjectFinancialReportItem {
        if ($amount <= 0) {
            return null;
        }

        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return null;
        }

        // Kasbon divisi dicatat pada kategori "Upah Pekerja" (sama dengan baris
        // agregat Upah Kerja dari payroll paid), bukan kategori kasbon terpisah.
        $category = $this->resolveUpahPekerjaCategory($userId);

        if (! $category) {
            return null;
        }

        $report = $this->getOrCreateForRecap($recap);

        $description = $this->teamKasbonDescription($division, $periodStart, $periodEnd);

        $item = $report->items()
            ->where('transaction_category_id', $category->id)
            ->where('description', $description)
            ->first();

        $data = [
            'transaction_category_id' => $category->id,
            'transaction_date' => $date ? Carbon::parse($date)->format('Y-m-d') : now()->toDateString(),
            'description' => $description,
            'expense_amount' => $amount,
        ];

        if ($item) {
            $item->update($data);
            $this->flushUsedCategoryCache($userId);

            return $item;
        }

        return $this->createItem($report, $data, null);
    }

    /**
     * Menyesuaikan baris kasbon pada Laporan Keuangan setelah sebagian payroll
     * paid pada proyek + periode dihapus.
     *
     * - Kasbon Divisi (pengeluaran) dihitung ulang dari KasbonPayment
     *   payroll_deduction yang masih terhubung payroll paid tersisa.
     * - Baris "Kasbon Pak {nama}" (informasi) dihapus total — kasbon personal
     *   tidak lagi dicatat pada Laporan Keuangan Proyek.
     *
     * Baris yang tidak lagi didukung data akan dihapus agar laporan tidak
     * meninggalkan baris yatim.
     *
     * @param  string  $projectName
     * @param  string  $periodStart  Tanggal mulai periode (Y-m-d)
     * @param  string|null  $periodEnd  Tanggal akhir periode (Y-m-d)
     * @param  int|string|null  $userId
     */
    public function reconcileKasbonExpenseItems(string $projectName, string $periodStart, ?string $periodEnd, $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return;
        }

        $recap = ProjectRecap::whereRaw('LOWER(project_name) = ?', [mb_strtolower(trim($projectName))])->first();

        if (! $recap) {
            return;
        }

        $report = ProjectFinancialReport::where('project_recap_id', $recap->id)->first();

        if (! $report) {
            return;
        }

        // Kasbon divisi & personal dicatat pada kategori "Upah Pekerja" (sama
        // dengan baris agregat Upah Kerja payroll paid), bukan kategori kasbon.
        $category = $this->resolveUpahPekerjaCategory($userId);

        if (! $category) {
            return;
        }

        $periodStartDate = Carbon::parse($periodStart)->format('Y-m-d');
        $periodEndDate = $periodEnd ? Carbon::parse($periodEnd)->format('Y-m-d') : null;

        $remainingPayrolls = Payroll::with('employee')
            ->where('created_by', $userId)
            ->where('project_name', $projectName)
            ->where('status', 'paid')
            ->where('period_start_date', $periodStartDate)
            ->when($periodEndDate, fn ($q) => $q->where('period_end_date', $periodEndDate), fn ($q) => $q->whereNull('period_end_date'))
            ->get();

        $remainingPayrollIds = $remainingPayrolls->pluck('id');

        // ── Baris Kasbon Divisi (pengeluaran) ──────────────────────────────
        $teamGroups = KasbonPayment::whereIn('payroll_id', $remainingPayrollIds)
            ->where('payment_method', 'payroll_deduction')
            ->whereHas('kasbon', fn ($q) => $q->where('kasbon_type', 'team'))
            ->with('kasbon')
            ->get()
            ->groupBy(fn (KasbonPayment $payment) => $this->teamKasbonDescription(
                $payment->kasbon->division,
                $payment->kasbon->period_start_date,
                $payment->kasbon->period_end_date
            ));

        $validTeamDescriptions = $teamGroups->keys();

        foreach ($teamGroups as $description => $payments) {
            $firstKasbon = $payments->first()->kasbon;
            $this->upsertTeamKasbonExpenseItem(
                $recap,
                $firstKasbon->division,
                $firstKasbon->period_start_date,
                $firstKasbon->period_end_date,
                (int) $payments->sum('amount'),
                now()->toDateString(),
                $userId
            );
        }

        $teamQuery = $report->items()
            ->where('transaction_category_id', $category->id)
            ->where('description', 'LIKE', 'Kasbon Divisi%');

        if ($validTeamDescriptions->isNotEmpty()) {
            $teamQuery->whereNotIn('description', $validTeamDescriptions);
        }

        $teamQuery->get()->each(function ($item) {
            $this->deleteProofFile($item->proof_file);
            $item->delete();
        });

        // ── Baris "Kasbon Pak {nama}" (informasi) dihapus total ─────────────
        // Kasbon personal tidak lagi dicatat sebagai baris informasi pada
        // Laporan Keuangan Proyek. Sisa baris lama dibersihkan agar idempoten.
        $report->items()
            ->where('transaction_category_id', $category->id)
            ->where('is_informational', true)
            ->where('description', 'LIKE', 'Kasbon Pak%')
            ->get()
            ->each(function ($item) {
                $this->deleteProofFile($item->proof_file);
                $item->delete();
            });

        $this->flushUsedCategoryCache($userId);
    }

    /**
     * Membangun keterangan baris kasbon divisi.
     */
    private function teamKasbonDescription(string $division, $periodStart, $periodEnd): string
    {
        return 'Kasbon Divisi '.$division.' periode '.Carbon::parse($periodStart)->format('d/m/Y')
            .($periodEnd ? ' - '.Carbon::parse($periodEnd)->format('d/m/Y') : '');
    }

    /**
     * Hapus item Laporan Keuangan Proyek yang dihasilkan dari bukti pembayaran.
     *
     * Hanya menghapus record; file bukti tetap milik PaymentProof dan dikelola
     * oleh modul Bukti Pembayaran sendiri.
     */
    public function deleteFromPaymentProof(PaymentProof $proof): void
    {
        ProjectFinancialReportItem::where('payment_proof_id', $proof->id)->delete();
        $this->flushUsedCategoryCache($proof->created_by ?? auth()->id());
    }

    /**
     * Menyinkronkan seluruh transaksi "Bon" hasil edit laporan keuangan proyek.
     *
     * Dipakai oleh modal edit dengan struktur dinamis (mirip tambah):
     * - item yang mengirim `id` diupdate (bukti diganti jika ada file baru)
     * - item tanpa `id` dianggap transaksi baru (dibuat)
     * - item existing yang tidak lagi dikirim (blok dihapus user) dihapus,
     *   KECUALI item yang dibuat otomatis dari bukti pembayaran
     *   (payment_proof_id terisi). Item tersebut tidak boleh dihapus dari
     *   modul ini — hanya bisa hilang bila bukti pembayarannya dihapus di
     *   modul Bukti Pembayaran (lihat deleteFromPaymentProof).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, \Illuminate\Http\UploadedFile|null>>  $proofFiles
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Report\ProjectFinancialReportItem>
     */
    public function syncItems(ProjectFinancialReport $report, array $items, array $proofFiles)
    {
        $existingItems = $report->items()->get();
        $submittedIds = [];

        foreach ($items as $index => $itemData) {
            $proofFile = $proofFiles[$index]['proof_file'] ?? null;
            $itemId = isset($itemData['id']) && trim((string) $itemData['id']) !== ''
                ? (string) $itemData['id']
                : null;

            if ($itemId) {
                $submittedIds[] = $itemId;

                $existingItem = $existingItems->firstWhere('id', (int) $itemId);

                if ($existingItem) {
                    $this->updateItem($existingItem, $itemData, $proofFile);

                    continue;
                }
            }

            $this->createItem($report, $itemData, $proofFile);
        }

        // Item existing yang bloknya dihapus pada form edit ikut dihapus,
        // kecuali item otomatis (bukti pembayaran, informasi, upah kerja,
        // kasbon divisi) yang tidak bisa dihapus user.
        $removedIds = $existingItems
            ->filter(fn ($item) => ! $item->isAutoGenerated())
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->diff($submittedIds)
            ->values()
            ->all();

        if ($removedIds) {
            $this->bulkDeleteItems($removedIds);
        }

        return $this->getItems($report);
    }

    /**
     * Mengupdate item "Bon" yang sudah ada.
     *
     * Bukti pembayaran hanya diganti jika ada file baru yang diunggah;
     * file lama dihapus setelah data berhasil disimpan.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(ProjectFinancialReportItem $item, array $data, ?UploadedFile $proofFile): bool
    {
        $oldFilePath = $item->proof_file;
        $storedFile = null;

        try {
            $data = $this->resolveAmounts($data);

            if ($proofFile) {
                $storedFile = $this->storeProofFile($proofFile);
                $data['proof_file'] = $storedFile['file_path'];
                $data['proof_file_name'] = $storedFile['file_name'];
            }

            $updated = $item->update($data);

            if ($storedFile && $oldFilePath && $oldFilePath !== $storedFile['file_path']) {
                $this->deleteProofFile($oldFilePath);
            }

            return $updated;
        } catch (\Throwable $throwable) {
            if (isset($storedFile['file_path'])) {
                $this->deleteProofFile($storedFile['file_path']);
            }
            throw $throwable;
        }
    }

    /**
     * Menentukan income_amount / expense_amount berdasarkan tipe kategori.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveAmounts(array $data): array
    {
        $category = TransactionCategory::find($data['transaction_category_id']);
        $amount = InputNormalizer::normalizeCurrency($data['expense_amount'] ?? null);

        if ($category && $category->type === TransactionCategory::TYPE_INCOME) {
            $data['income_amount'] = $amount;
            $data['expense_amount'] = null;
        } else {
            $data['income_amount'] = null;
            $data['expense_amount'] = $amount;
        }

        return $data;
    }

    /**
     * Hapus beberapa item "Bon" sekaligus (bulk delete).
     *
     * File bukti pembayaran milik item yang dihapus ikut dibersihkan.
     *
     * @param  array<int>  $ids  Daftar ID item
     * @return int Jumlah item yang dihapus
     */
    public function bulkDeleteItems(array $ids): int
    {
        $deletedCount = 0;

        ProjectFinancialReportItem::whereIn('id', $ids)->each(function ($item) use (&$deletedCount) {
            $this->deleteProofFile($item->proof_file);
            $item->delete();
            $deletedCount++;
        });

        if ($deletedCount > 0) {
            $this->flushUsedCategoryCache(auth()->id());
        }

        return $deletedCount;
    }

    /**
     * Generate unique laporan keuangan proyek ID (format: LFP-00001).
     *
     * Prefix: LFP (Laporan Financial Proyek)
     * Sequential number: 5 digit zero-padded.
     *
     * Menggunakan database lock untuk mencegah race condition.
     */
    public function generateId(): string
    {
        $lastReport = ProjectFinancialReport::lockForUpdate()
            ->where('id', 'like', 'LFP-%')
            ->orderByDesc('id')
            ->first();

        if ($lastReport && preg_match('/^LFP-(\d+)$/', $lastReport->id, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $newId = 'LFP-'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        while (ProjectFinancialReport::where('id', $newId)->exists()) {
            $nextNumber++;
            $newId = 'LFP-'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newId;
    }

    /**
     * Ambil semua kategori transaksi aktif milik modul Laporan Keuangan Proyek.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Report\TransactionCategory>
     */
    public function getProjectFinanceCategories()
    {
        $userId = auth()->id();
        $cacheKey = 'finance:project-finance-categories:'.$userId;

        $query = fn () => TransactionCategory::where('created_by', $userId)
            ->module(TransactionCategory::MODULE_PROJECT_FINANCE)
            ->active()
            ->orderBy('sort_order')
            ->get();

        try {
            return Cache::remember($cacheKey, now()->addDay(), $query);
        } catch (\Exception $e) {
            Log::warning('Cache READ error ['.$cacheKey.']: '.$e->getMessage());

            return $query();
        }
    }

    /**
     * Invalidate cache kategori modul Laporan Keuangan Proyek.
     */
    public function flushCategoryCache(?string $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        try {
            Cache::forget('finance:project-finance-categories:'.$userId);
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [finance:project-finance-categories]: '.$e->getMessage());
        }
    }

    /**
     * Invalidate cache "kategori sedang digunakan" milik modul Report.
     *
     * Cache report:category-used-ids menentukan kategori yang tidak boleh
     * dihapus di halaman Kategori Transaksi. Karena kini kategori ikut
     * dipakai item Laporan Keuangan Proyek, setiap item dibuat/dihapus
     * (termasuk sinkronisasi dari bukti pembayaran) cache ini harus
     * di-invalidate agar halaman kategori tidak menyajikan data basi.
     */
    private function flushUsedCategoryCache(?string $userId = null): void
    {
        app(TransactionCategoryService::class)->flushCache($userId);
    }

    /**
     * Menyimpan file bukti pembayaran.
     *
     * File disimpan apa adanya dengan nama UUID di Storage::disk('public').
     * Path yang disimpan ke DB adalah path RELATIF agar portabel antar server.
     *
     * @return array{file_name: string, file_path: string}
     */
    private function storeProofFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $fileName = Str::uuid()->toString().'.'.$extension;
        $relativePath = self::PROOF_DIRECTORY.'/'.$fileName;

        $file->storeAs(self::PROOF_DIRECTORY, $fileName, ['disk' => 'public']);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
        ];
    }

    /**
     * Menghapus file bukti pembayaran berdasarkan path relatif.
     */
    private function deleteProofFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }
}
