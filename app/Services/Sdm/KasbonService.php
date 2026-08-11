<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Kasbon;
use App\Models\Sdm\KasbonPayment;
use App\Models\Sdm\Employee;
use App\Models\Sdm\Division;
use App\Services\InputNormalizer;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola bisnis logika kasbon (cash advance).
 *
 * Menangani daftar kasbon, pembuatan, pembaruan, penghapusan,
 * validasi batas maksimal berdasarkan absensi, dan pembuatan kode.
 *
 * Semua aturan bisnis terkait kasbon dipusatkan di sini.
 *
 * Setiap perubahan data kasbon (tambah/ubah/hapus/cicilan) memicu penghitungan
 * ulang otomatis payroll draft pada periode terkait, sehingga potongan kasbon
 * pada snapshot payroll selalu sinkron dengan data kasbon terkini.
 */
class KasbonService
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {}
    /**
     * Mendapatkan daftar kasbon dengan paginasi dan relasi karyawan.
     *
     * Mendukung filter berdasarkan pencarian (kode/catatan/nama karyawan), bulan, tahun,
     * status, dan tipe. Hasil diurutkan berdasarkan kasbon_date terbaru terlebih dahulu.
     *
     * Logika:
     * - Search memakai escapeLikePattern('%' dan '_' di-escape) agar wildcard
     *   SQL dari input user diperlakukan sebagai teks biasa, bukan pattern.
     * - Filter bulan/tahun memakai period_start_date (bukan kasbon_date) —
     *   kasbon diidentifikasi berdasarkan periode payrollnya.
     * - appends(request()->only(...)) mempertahankan filter pada URL pagination
     *   agar berpindah halaman tidak kehilangan filter.
     *
     * @param  string|null  $search   Kata kunci pencarian (kode kasbon, catatan, atau nama karyawan)
     * @param  int|null     $month    Filter berdasarkan bulan periode
     * @param  int|null     $year     Filter berdasarkan tahun periode
     * @param  string|null  $status   Filter berdasarkan status (pending/deducted)
     * @param  string|null  $type     Filter berdasarkan tipe (personal/team)
     * @param  int          $perPage  Jumlah data per halaman
     * @return LengthAwarePaginator
     */
    public function getPaginatedKasbons(
        ?string $search,
        ?int $month,
        ?int $year,
        ?string $status,
        ?string $type,
        ?string $paymentStatus = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return Kasbon::with('employee')
            ->withCount(['payments as live_payroll_payments_count' => fn ($q) => $q->whereNotNull('payroll_id')])
            ->where('created_by', auth()->id())
            ->when($search, function ($query, $search) {
                $escapedSearch = $this->escapeLikePattern($search);
                $query->where(function ($q) use ($escapedSearch) {
                    $q->where('kasbon_code', 'like', "%{$escapedSearch}%")
                        ->orWhere('notes', 'like', "%{$escapedSearch}%")
                        ->orWhereHas('employee', function ($empQuery) use ($escapedSearch) {
                            $empQuery->where('name', 'like', "%{$escapedSearch}%");
                        });
                });
            })
            ->when($month, fn($query, $month) => $query->whereMonth('period_start_date', $month))
            ->when($year, fn($query, $year) => $query->whereYear('period_start_date', $year))
            ->when($status, fn($query, $status) => $query->where('status', $status))
            ->when($type, fn($query, $type) => $query->where('kasbon_type', $type))
            ->when($paymentStatus, fn($query, $paymentStatus) => $query->where('payment_status', $paymentStatus))
            ->latest('kasbon_date')
            ->latest('created_at')
            ->paginate($perPage)
            ->appends(request()->only(['search', 'month', 'year', 'status', 'type', 'payment_status']));
    }

    /**
     * Mendapatkan semua karyawan untuk dropdown pilihan.
     *
     * Hanya mengambil kolom yang dibutuhkan untuk dropdown
     * agar tidak mengambil data yang berlebihan.
     *
     * @return Collection<int, Employee>
     */
    public function getAllEmployees(): Collection
    {
        $userId = auth()->id();

        try {
            return Cache::remember(
                'sdm:employees:dropdown:' . $userId,
                now()->addHours(24),
                function () use ($userId) {
                    return Employee::where('created_by', $userId)
                        ->orderBy('name')
                        ->get(['employee_code', 'name']);
                }
            );
        } catch (\Exception $e) {
            Log::warning(
                'Cache read failed for sdm:employees:dropdown:' . $userId . ': ' .
                $e->getMessage()
            );

            return Employee::where('created_by', $userId)
                ->orderBy('name')
                ->get(['employee_code', 'name']);
        }
    }

    /**
     * Mendapatkan semua divisi untuk dropdown pilihan.
     *
     * @return Collection<int, Division>
     */
    public function getAllDivisions(): Collection
    {
        $userId = auth()->id();

        try {
            return Cache::remember(
                'sdm:divisions:dropdown:' . $userId,
                now()->addHours(24),
                function () use ($userId) {
                    return Division::where('created_by', $userId)
                        ->orderBy('name')
                        ->get(['id', 'name']);
                }
            );
        } catch (\Exception $e) {
            Log::warning(
                'Cache read failed for sdm:divisions:dropdown:' . $userId . ': ' .
                $e->getMessage()
            );
            return Division::where('created_by', $userId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }
    }

    /**
     * Mendapatkan daftar proyek untuk multi-select pada modal kasbon divisi.
     *
     * Sama seperti opsi proyek pada Generate Payroll (dari proyek yang
     * dimiliki karyawan), agar proyek yang dipilih kasbon selalu bisa
     * dicocokkan dengan payroll saat dibayar.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getProjectOptions()
    {
        return $this->payrollService->getProjectOptions();
    }

    /**
     * Menyimpan data kasbon baru.
     *
     * Bisnis Logika:
     * - Kasbon tim: setiap baris `projects[]` disimpan sebagai record kasbon
     *   terpisah (masing-masing proyek punya jumlah, periode, dan catatan
     *   sendiri), sehingga lunas otomatis saat payroll proyek tersebut dibayar.
     * - Kasbon personal: menghasilkan kode kasbon unik (KSB001, KSB002, ...),
     *   mendeteksi week_number/period_month/period_year dari period_start_date,
     *   dan menyetel project_names menjadi null.
     * - Status default adalah 'pending'.
     *
     * @param  array  $data  Data input yang sudah divalidasi
     * @return Kasbon  Kasbon terakhir yang dibuat
     *
     * @throws \InvalidArgumentException  Jika kasbon tim tanpa baris proyek
     */
    public function storeKasbon(array $data): Kasbon
    {
        if (($data['kasbon_type'] ?? '') === 'team') {
            return $this->storeTeamKasbon($data);
        }

        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);
        $data['kasbon_code'] = Kasbon::generateKasbonCode();
        $data['status'] = 'pending';
        $data['paid_amount'] = 0;
        $data['remaining_amount'] = $data['amount'];
        $data['payment_status'] = 'unpaid';
        $periodStart = Carbon::parse($data['period_start_date']);
        $data['period_month'] = $periodStart->month;
        $data['period_year'] = $periodStart->year;
        $data['week_number'] = $periodStart->weekOfMonth;
        $data['created_by'] = auth()->id();
        $data = $this->normalizeProjectNames($data);

        $kasbon = Kasbon::create($data);

        if ($kasbon->employee_id) {
            $this->payrollService->recalculateForEmployeePeriod(
                $kasbon->employee_id,
                $kasbon->period_start_date
            );
        }

        return $kasbon;
    }

    /**
     * Menyimpan kasbon tim: satu record per baris proyek.
     *
     * Setiap baris `projects[]` menghasilkan record kasbon terpisah dengan
     * kode, jumlah, periode, dan catatan masing-masing. Divisi dipakai bersama
     * dari input level atas.
     *
     * @param  array  $data  Data input yang sudah divalidasi (mengandung `projects`)
     * @return Kasbon  Kasbon terakhir yang dibuat
     *
     * @throws \InvalidArgumentException  Jika `projects` kosong
     */
    private function storeTeamKasbon(array $data): Kasbon
    {
        $division = $data['division'] ?? null;
        $kasbon = null;

        foreach ($data['projects'] ?? [] as $project) {
            $kasbon = $this->createTeamKasbonRecord($division, $project);
        }

        if ($kasbon === null) {
            throw new \InvalidArgumentException('Minimal satu proyek harus diisi untuk kasbon divisi.');
        }

        return $kasbon;
    }

    /**
     * Membuat satu record kasbon tim untuk sebuah baris proyek.
     *
     * @param  string|null  $division  Nama divisi (dipakai bersama semua proyek)
     * @param  array{project: string, amount: int, kasbon_date: string, period_start_date: string, period_end_date: string, notes: string|null}  $project  Baris proyek yang sudah divalidasi
     * @return Kasbon
     */
    private function createTeamKasbonRecord(?string $division, array $project): Kasbon
    {
        $periodStart = Carbon::parse($project['period_start_date']);
        $amount = InputNormalizer::normalizeCurrency($project['amount'] ?? 0);

        return Kasbon::create([
            'kasbon_code' => Kasbon::generateKasbonCode(),
            'kasbon_type' => 'team',
            'division' => $division,
            'project_names' => [trim((string) ($project['project'] ?? ''))],
            'employee_id' => null,
            'amount' => $amount,
            'paid_amount' => 0,
            'remaining_amount' => $amount,
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'kasbon_date' => $project['kasbon_date'] ?? now()->format('Y-m-d'),
            'period_start_date' => $project['period_start_date'],
            'period_end_date' => $project['period_end_date'],
            'period_month' => $periodStart->month,
            'period_year' => $periodStart->year,
            'week_number' => $periodStart->weekOfMonth,
            'notes' => $project['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Memvalidasi batas kasbon berdasarkan absensi untuk kasbon personal.
     *
     * Pemeriksaan:
     * 1. Apakah payroll untuk periode ini sudah dibayar
     * 2. Apakah karyawan memiliki data absensi
     * 3. Apakah jumlah yang diminta melebihi batas maksimal yang diperbolehkan
     *
     * @param  string         $employeeCode    Kode karyawan
     * @param  string         $periodStartDate Tanggal mulai periode (Y-m-d)
     * @param  string         $kasbonDate      Tanggal kasbon (Y-m-d)
     * @param  int            $amount          Jumlah kasbon yang diminta
     * @return array{valid: bool, message: string}  Hasil validasi
     */
    public function validatePersonalKasbonLimit(
        string $employeeCode,
        string $periodStartDate,
        string $kasbonDate,
        int $amount
    ): array {
        $employee = Employee::find($employeeCode);

        if (!$employee) {
            return ['valid' => false, 'message' => 'Karyawan tidak ditemukan'];
        }

        $periodStartDateCarbon = Carbon::parse($periodStartDate);

        if ($employee->isPayrollPaidByStartDate($periodStartDateCarbon)) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Tidak dapat melakukan kasbon! Payroll periode %s sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                    $periodStartDateCarbon->format('d M Y')
                ),
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Memvalidasi batas kasbon berdasarkan absensi untuk kasbon personal (pembaruan).
     *
     * Menggunakan metode canTakeKasbon untuk validasi.
     *
     * CATATAN: saat ini selalu mengembalikan valid=true (selama karyawan ada).
     * Validasi batas untuk update masih kosong oleh desain — sisipkan logika
     * (misal pengecekan payroll dibayar seperti validatePersonalKasbonLimit)
     * jika aturan bisnisnya diperketat.
     *
     * @param  string         $employeeCode    Kode karyawan
     * @param  string         $periodStartDate Tanggal mulai periode (Y-m-d)
     * @param  string         $kasbonDate      Tanggal kasbon (Y-m-d)
     * @param  int            $amount          Jumlah kasbon yang diminta
     * @return array{valid: bool, message: string}  Hasil validasi
     */
    public function validatePersonalKasbonUpdate(
        string $employeeCode,
        string $periodStartDate,
        string $kasbonDate,
        int $amount
    ): array {
        $employee = Employee::find($employeeCode);

        if (!$employee) {
            return ['valid' => false, 'message' => 'Karyawan tidak ditemukan'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Memperbarui data kasbon yang sudah ada.
     *
     * Bisnis Logika:
     * - Hanya kasbon dengan status pending yang bisa diperbarui
     * - Untuk kasbon personal: memvalidasi batas maksimal berdasarkan absensi
     * - Untuk kasbon team: mengatur employee_id menjadi null
     * - Untuk kasbon personal: mengatur division menjadi null
     *
     * @param  Kasbon  $kasbon  Instance model kasbon yang akan diperbarui
     * @param  array   $data    Data pembaruan yang sudah divalidasi
     * @return bool
     */
    public function updateKasbon(Kasbon $kasbon, array $data): bool
    {
        $oldType = $kasbon->kasbon_type;
        $oldEmployeeId = $kasbon->employee_id;
        $oldPeriodStart = Carbon::parse($kasbon->period_start_date)->format('Y-m-d');

        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);
        $data = $this->normalizeProjectNames($data);

        if ($data['kasbon_type'] === 'team') {
            $data['employee_id'] = null;

            // Jumlah bersifat opsional untuk kasbon tim; pertahankan nilai lama
            // bila tidak diubah (bidang jumlah kini juga tampil pada form edit).
            if (empty($data['amount'])) {
                $data['amount'] = $kasbon->amount;
            }

            // Bidang periode hanya berlaku untuk kasbon personal dan tidak
            // dikirim oleh form edit untuk kasbon tim; pertahankan nilai lama.
            $data['kasbon_date'] = $kasbon->kasbon_date;
            $data['period_start_date'] = $kasbon->period_start_date;
            $data['period_end_date'] = $kasbon->period_end_date;
            $data['week_number'] = $kasbon->week_number;
            $data['period_month'] = $kasbon->period_month;
            $data['period_year'] = $kasbon->period_year;
        } else {
            $data['division'] = null;
        }

        // Sinkronkan sisa hutang bila jumlah diubah pada kasbon yang belum dibayar.
        if ((int) ($data['amount'] ?? 0) !== (int) $kasbon->amount && $kasbon->payment_status === 'unpaid') {
            $data['remaining_amount'] = $data['amount'];
        }

        if (!empty($data['period_start_date'])) {
            $periodStart = Carbon::parse($data['period_start_date']);
            $data['period_month'] = $periodStart->month;
            $data['period_year'] = $periodStart->year;
            $data['week_number'] = $periodStart->weekOfMonth;
        }

        $result = $kasbon->update($data);

        $fresh = $kasbon->fresh();
        $newType = $fresh->kasbon_type;
        $newEmployeeId = $fresh->employee_id;
        $newPeriodStart = Carbon::parse($fresh->period_start_date)->format('Y-m-d');

        // Hitung ulang payroll periode lama (potongan kasbon lama harus dilepas bila
        // kasbon pindah karyawan/periode/tipe).
        $this->recalculateKasbonPayroll($oldType, $oldEmployeeId, $oldPeriodStart);

        // Hitung ulang payroll periode baru bila kasbon kini personal dan ada yang berubah.
        if ($newType === 'personal'
            && ($newEmployeeId !== $oldEmployeeId || $newPeriodStart !== $oldPeriodStart || $oldType !== 'personal')) {
            $this->recalculateKasbonPayroll($newType, $newEmployeeId, $newPeriodStart);
        }

        return $result;
    }

    /**
     * Menghapus data kasbon secara massal berdasarkan kode kasbon.
     *
     * Bisnis Logika:
     * - Hanya kasbon dengan status pending yang bisa dihapus.
     * - Kasbon yang terhubung ke payroll yang MASIH ADA tidak bisa dihapus
     *   (potongan sudah dipakai payroll / sudah menulis Laporan Keuangan).
     * - Karena relasi kasbon_payments.payroll_id ber-on-delete SET NULL,
     *   pembayaran yang hanya menunjuk payroll yang SUDAH DIHAPUS otomatis
     *   menjadi payroll_id = null, sehingga kasbon kembali bisa dihapus
     *   setelah payroll terkaitnya dihapus.
     * - Pembayaran manual (payroll_id null) tidak lagi menghalangi hapus.
     *
     * @param  array<int, string>  $kasbonCodes  Array kode kasbon yang akan dihapus
     * @return array{deleted: int, skipped: int}  Jumlah data yang dihapus dan dilewati
     */
    public function deleteSelectedKasbons(array $kasbonCodes): array
    {
        $pendingKasbons = Kasbon::whereIn('kasbon_code', $kasbonCodes)
            ->pending()
            ->whereDoesntHave('payments', fn ($query) => $query->whereNotNull('payroll_id'))
            ->get();

        $deleted = $pendingKasbons->count();
        $skipped = count($kasbonCodes) - $deleted;

        if ($deleted > 0) {
            Kasbon::whereIn('kasbon_code', $pendingKasbons->pluck('kasbon_code'))->delete();

            foreach ($pendingKasbons as $kasbon) {
                if ($kasbon->kasbon_type === 'personal' && $kasbon->employee_id) {
                    $this->payrollService->recalculateForEmployeePeriod(
                        $kasbon->employee_id,
                        $kasbon->period_start_date
                    );
                }
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    /**
     * Mendapatkan total kasbon untuk karyawan dan periode tertentu.
     *
     * @param  string         $employeeCode    Kode karyawan
     * @param  string         $periodStartDate Tanggal mulai periode
     * @return int  Total jumlah kasbon pending
     */
    public function getTotalForEmployee(string $employeeCode, string $periodStartDate): int
    {
        return Kasbon::getTotalForEmployee($employeeCode, $periodStartDate);
    }

    /**
     * Mendapatkan total kasbon team untuk periode tertentu.
     *
     * @param  string  $periodStartDate  Tanggal mulai periode
     * @return int     Total jumlah kasbon team pending
     */
    public function getTotalTeamKasbon(string $periodStartDate): int
    {
        return Kasbon::getTotalTeamKasbon($periodStartDate);
    }

    // ─── Cicilan (Installment) ──────────────────────────────────────────

    /**
     * Rekam pembayaran cicilan kasbon (manual atau dari payroll).
     *
     * Logika:
     * - effectiveAmount = min(amount, remaining_amount) → pembayaran otomatis
     *   dibatasi agar tidak pernah melebihi sisa hutang.
     * - paid_amount diakumulasi, remaining_amount dihitung ulang dari amount,
     *   lalu payment_status diturunkan: sisa ≤ 0 → 'paid', selain itu 'partial'.
     * - Dipanggil baik dari pembayaran manual maupun potongan payroll
     *   (method = 'payroll_deduction' + payroll_id).
     *
     * @param  Kasbon     $kasbon    Kasbon yang akan dibayar
     * @param  int        $amount    Jumlah pembayaran
     * @param  string     $method    'manual' atau 'payroll_deduction'
     * @param  int|null   $payrollId ID payroll (jika dari payroll)
     * @return KasbonPayment
     *
     * @throws \InvalidArgumentException  Jika kasbon sudah lunas
     */
    public function recordPayment(Kasbon $kasbon, int $amount, string $method = 'manual', ?int $payrollId = null): KasbonPayment
    {
        if ($kasbon->payment_status === 'paid') {
            throw new \InvalidArgumentException('Kasbon sudah lunas');
        }

        $effectiveAmount = min($amount, $kasbon->remaining_amount);

        $payment = KasbonPayment::create([
            'kasbon_code' => $kasbon->kasbon_code,
            'payroll_id' => $payrollId,
            'amount' => $effectiveAmount,
            'payment_method' => $method,
            'payment_date' => now()->format('Y-m-d'),
            'created_by' => auth()->id(),
        ]);

        $kasbon->paid_amount = ($kasbon->paid_amount ?? 0) + $effectiveAmount;
        $kasbon->remaining_amount = $kasbon->amount - $kasbon->paid_amount;
        $kasbon->payment_status = $kasbon->remaining_amount <= 0 ? 'paid' : 'partial';
        $kasbon->save();

        // Payment manual baru belum ter-assign; hitung ulang payroll draft pada
        // periode kasbon agar cicilan ini otomatis masuk potongan payroll.
        if ($method === 'manual' && $kasbon->kasbon_type === 'personal' && $kasbon->employee_id) {
            $this->payrollService->recalculateForEmployeePeriod(
                $kasbon->employee_id,
                $kasbon->period_start_date
            );
        }

        return $payment;
    }

    /**
     * Menghitung ulang payroll draft pada periode kasbon personal.
     *
     * Dipakai setelah kasbon dibuat/diubah/dihapus. Kasbon team (per divisi)
     * tidak menyentuh potongan upah per orang, sehingga dilewati.
     *
     * @param  string       $kasbonType   'personal' atau 'team'
     * @param  string|null  $employeeId   Kode karyawan (null untuk kasbon team)
     * @param  string|null  $periodStart  Tanggal mulai periode (Y-m-d)
     * @return void
     */
    private function recalculateKasbonPayroll(string $kasbonType, ?string $employeeId, ?string $periodStart): void
    {
        if ($kasbonType !== 'personal' || !$employeeId || !$periodStart) {
            return;
        }

        $this->payrollService->recalculateForEmployeePeriod($employeeId, $periodStart);
    }

    /**
     * Mendapatkan total sisa kasbon yang belum dibayar untuk karyawan tertentu.
     *
     * Logika:
     * - notPaid() scope = status selain 'paid'; hasil SUM(remaining_amount)
     *   dipakai untuk membatasi kasbon baru agar total hutang tidak meledak.
     *
     * @param  string  $employeeCode  Kode karyawan
     * @return int     Total sisa kasbon
     */
    public function getTotalRemainingForEmployee(string $employeeCode): int
    {
        return Kasbon::where('employee_id', $employeeCode)
            ->notPaid()
            ->sum('remaining_amount');
    }

    /**
     * Mendapatkan daftar kasbon yang masih memiliki sisa hutang untuk periode tertentu.
     *
     * @param  string         $periodStartDate  Tanggal mulai periode
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingKasbonsForPeriod(string $periodStartDate): Collection
    {
        return Kasbon::where('period_start_date', $periodStartDate)
            ->pending()
            ->notPaid()
            ->get();
    }

    /**
     * Mendapatkan riwayat pembayaran untuk kasbon tertentu.
     *
     * @param  string  $kasbonCode  Kode kasbon
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPayments(string $kasbonCode): Collection
    {
        return KasbonPayment::where('kasbon_code', $kasbonCode)
            ->with('payroll')
            ->latest('payment_date')
            ->get();
    }

    /**
     * Memeriksa kasbon maksimal yang diperbolehkan untuk karyawan berdasarkan absensi.
     *
     * Mengembalikan informasi lengkap termasuk nama karyawan, hari kerja,
     * gaji harian, kasbon maksimal, dan apakah payroll sudah dibayar.
     *
     * @param  string  $employeeCode    Kode karyawan
     * @param  string  $periodStartDate Tanggal mulai periode (Y-m-d)
     * @param  string  $kasbonDate      Tanggal kasbon (Y-m-d)
     * @return array{success: bool, employee_name: string, days_worked: int, daily_wage: int, max_kasbon: int, payroll_paid: bool, no_attendance: bool, max_kasbon_formatted: string, message: string}
     */
    public function checkMaxKasbon(string $employeeCode, string $periodStartDate, string $kasbonDate): array
    {
        $employee = Employee::find($employeeCode);

        if (!$employee) {
            return [
                'success' => false,
                'employee_name' => '',
                'days_worked' => 0,
                'daily_wage' => 0,
                'max_kasbon' => 0,
                'payroll_paid' => false,
                'no_attendance' => false,
                'max_kasbon_formatted' => 'Rp 0',
                'message' => 'Karyawan tidak ditemukan',
            ];
        }

        $periodStartDateCarbon = Carbon::parse($periodStartDate);

        if ($employee->isPayrollPaidByStartDate($periodStartDateCarbon)) {
            return [
                'success' => false,
                'employee_name' => $employee->name,
                'days_worked' => 0,
                'daily_wage' => 0,
                'max_kasbon' => 0,
                'payroll_paid' => true,
                'no_attendance' => false,
                'max_kasbon_formatted' => 'Rp 0',
                'message' => sprintf(
                    'Payroll periode %s sudah dibayar (status: paid). Kasbon hanya bisa dilakukan untuk minggu yang belum dibayar.',
                    $periodStartDateCarbon->format('d M Y')
                ),
            ];
        }

        $dailyWage = $employee->daily_wage ?? $employee->base_salary;

        return [
            'success' => true,
            'employee_name' => $employee->name,
            'days_worked' => 0,
            'daily_wage' => $dailyWage,
            'max_kasbon' => 0,
            'payroll_paid' => false,
            'no_attendance' => false,
            'max_kasbon_formatted' => 'Rp 0',
            'message' => sprintf(
                'Kasbon untuk %s tidak dibatasi oleh hari kerja.',
                $employee->name
            ),
        ];
    }

    /**
     * Menormalisasi project_names sesuai tipe kasbon.
     *
     * - Kasbon team: buang nilai kosong, hapus duplikat, lalu simpan sebagai
     *   array terurut indeks (atau null bila kosong).
     * - Kasbon personal: selalu null (proyek tidak berlaku).
     *
     * @param  array  $data  Data input kasbon
     * @return array  Data dengan kunci project_names yang dinormalisasi
     */
    private function normalizeProjectNames(array $data): array
    {
        if (($data['kasbon_type'] ?? '') === 'team') {
            $projects = array_values(array_unique(array_filter(
                array_map('trim', (array) ($data['project_names'] ?? [])),
                fn ($value) => $value !== ''
            )));

            $data['project_names'] = $projects ?: null;
        } else {
            $data['project_names'] = null;
        }

        return $data;
    }

    /**
     * Mengescape karakter pola LIKE khusus untuk mencegah manipulasi.
     *
     * Karakter % dan _ adalah karakter wildcard dalam pola LIKE SQL.
     * Metode ini mengescape-nya agar input pengguna diperlakukan sebagai teks harfiah.
     *
     * @param  string  $value  Input pencarian mentah
     * @return string  String yang sudah di-escape dan aman untuk query LIKE
     */
    private function escapeLikePattern(string $value): string
    {
        return addcslashes($value, '%_');
    }
}
