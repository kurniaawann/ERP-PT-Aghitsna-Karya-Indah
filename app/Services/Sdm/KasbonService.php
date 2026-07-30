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
 */
class KasbonService
{
    /**
     * Mendapatkan daftar kasbon dengan paginasi dan relasi karyawan.
     *
     * Mendukung filter berdasarkan pencarian (kode/catatan/nama karyawan), bulan, tahun,
     * status, dan tipe. Hasil diurutkan berdasarkan kasbon_date terbaru terlebih dahulu.
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
        try {
            return Cache::remember('sdm:employees:dropdown', now()->addHours(24), function () {
                return Employee::orderBy('name')->get(['employee_code', 'name']);
            });
        } catch (\Exception $e) {
            Log::warning('Cache read failed for sdm:employees:dropdown: ' . $e->getMessage());
            return Employee::orderBy('name')->get(['employee_code', 'name']);
        }
    }

    /**
     * Mendapatkan semua divisi untuk dropdown pilihan.
     *
     * @return Collection<int, Division>
     */
    public function getAllDivisions(): Collection
    {
        try {
            return Cache::remember('sdm:divisions:dropdown', now()->addHours(24), function () {
                return Division::orderBy('name')->get(['id', 'name']);
            });
        } catch (\Exception $e) {
            Log::warning('Cache read failed for sdm:divisions:dropdown: ' . $e->getMessage());
            return Division::orderBy('name')->get(['id', 'name']);
        }
    }

    /**
     * Menyimpan data kasbon baru.
     *
     * Bisnis Logika:
     * - Menghasilkan kode kasbon unik (KSB001, KSB002, ...)
     * - Mendeteksi week_number secara otomatis dari period_start_date
     * - Untuk kasbon personal: memvalidasi batas maksimal berdasarkan absensi
     * - Untuk kasbon team: mengatur employee_id menjadi null
     * - Untuk kasbon personal: mengatur division menjadi null
     * - Status default adalah 'pending'
     *
     * @param  array{kasbon_type: string, employee_id: string|null, division: string|null, amount: int, kasbon_date: string, period_month: int, period_year: int, period_start_date: string, period_end_date: string, notes: string|null}  $data  Data input yang sudah divalidasi
     * @return Kasbon  Data kasbon yang dibuat
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi berdasarkan absensi gagal
     */
    public function storeKasbon(array $data): Kasbon
    {
        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);
        $data['kasbon_code'] = Kasbon::generateKasbonCode();
        $data['status'] = 'pending';
        $data['paid_amount'] = 0;
        $data['remaining_amount'] = $data['amount'];
        $data['payment_status'] = 'unpaid';
        $data['week_number'] = Carbon::parse($data['period_start_date'])->weekOfMonth;
        $data['created_by'] = auth()->id();

        if ($data['kasbon_type'] === 'team') {
            $data['employee_id'] = null;
        } else {
            $data['division'] = null;
        }

        return Kasbon::create($data);
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
        $data['amount'] = InputNormalizer::normalizeCurrency($data['amount'] ?? 0);

        if ($data['kasbon_type'] === 'team') {
            $data['employee_id'] = null;
        } else {
            $data['division'] = null;
        }

        return $kasbon->update($data);
    }

    /**
     * Menghapus data kasbon secara massal berdasarkan kode kasbon.
     *
     * Bisnis Logika:
     * - Hanya kasbon dengan status pending yang bisa dihapus
     * - Kasbon yang sudah deducted (dipotong) akan dilewati
     *
     * @param  array<int, string>  $kasbonCodes  Array kode kasbon yang akan dihapus
     * @return array{deleted: int, skipped: int}  Jumlah data yang dihapus dan dilewati
     */
    public function deleteSelectedKasbons(array $kasbonCodes): array
    {
        $pendingKasbons = Kasbon::whereIn('kasbon_code', $kasbonCodes)->pending()->get();

        $deleted = $pendingKasbons->count();
        $skipped = count($kasbonCodes) - $deleted;

        if ($deleted > 0) {
            Kasbon::whereIn('kasbon_code', $pendingKasbons->pluck('kasbon_code'))->delete();
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

        return $payment;
    }

    /**
     * Mendapatkan total sisa kasbon yang belum dibayar untuk karyawan tertentu.
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
