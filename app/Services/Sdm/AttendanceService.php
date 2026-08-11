<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Attendance;
use App\Models\Sdm\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola bisnis logika absensi.
 *
 * Menangani daftar absensi, pembuatan massal, pembaruan, penghapusan,
 * deteksi duplikat, dan semua aturan bisnis terkait absensi karyawan.
 *
 * Setiap perubahan data absensi (tambah/ubah/hapus) memicu penghitungan
 * ulang otomatis payroll draft yang periodenya menimpa tanggal yang berubah,
 * sehingga snapshot payroll (hari masuk, lembur, potongan, gaji bersih)
 * selalu sinkron dengan data absensi terkini.
 */
class AttendanceService
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {}
    /**
     * Mendapatkan daftar absensi dengan paginasi, pencarian, dan eager loading.
     *
     * Logika:
     * - with('employee') mencegah query N+1 saat menampilkan nama karyawan.
     * - where('created_by') membatasi data hanya milik user yang login.
     * - Pencarian memakai whereHas pada relasi employee (nama/kode) ATAU
     *   attendance_date — dibungkus satu group agar OR tidak merusak filter user.
     * - Urutan: tanggal terbaru dulu, lalu created_at sebagai tie-breaker.
     *
     * @param  string|null  $search     Kata kunci pencarian (nama karyawan, kode, atau tanggal)
     * @param  int          $perPage    Jumlah data per halaman
     * @return LengthAwarePaginator
     */
    public function getPaginatedAttendances(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Attendance::with('employee')
            ->where('created_by', auth()->id())
            ->when($search, function ($query, $search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                })
                    ->orWhere('attendance_date', 'like', "%{$search}%");
            })
            ->latest('attendance_date')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Mendapatkan semua karyawan yang diurutkan berdasarkan nama untuk pilihan formulir.
     *
     * Logika:
     * - Hanya mengambil kolom employee_code + name (data lengkap tidak dibutuhkan
     *   untuk dropdown) agar payload ringan.
     * - Hasil di-cache 24 jam di key 'sdm:employees:dropdown'; cache di-flush
     *   saat CRUD karyawan (lihat EmployeeService::flushCache). Fallback ke
     *   query langsung jika cache bermasalah.
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
     * Mendapatkan data absensi yang sudah ada, dikelompokkan berdasarkan employee_id
     * untuk validasi duplikat di sisi klien.
     *
     * Mengembalikan array asosiatif: ['EMP001' => ['2025-01-01', '2025-01-02'], ...]
     *
     * Logika:
     * - SELECT hanya employee_id + attendance_date (kolom minimal) untuk data ini.
     * - groupBy('employee_id') lalu pluck tanggal — format dinormalisasi ke
     *   Y-m-d agar bisa dibandingkan dengan tanggal pilihan user di frontend.
     *
     * @return array<string, array<int, string>>
     */
    public function getExistingAttendance(): array
    {
        return Attendance::select('employee_id', 'attendance_date')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($items) {
                return $items->pluck('attendance_date')
                    ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                    ->toArray();
            })
            ->toArray();
    }

    /**
     * Mencari data absensi duplikat untuk karyawan dan rentang tanggal tertentu.
     *
     * Melakukan satu query untuk mengambil semua data yang sudah ada dalam rentang,
     * kemudian memeriksa setiap kombinasi karyawan+tanggal terhadap hasil tersebut.
     *
     * Logika:
     * - Semua record existing diambil SEKALI lalu di-keyBy("employee_date") →
     *   lookup O(1) per kombinasi, bukan query per karyawan/tanggal (anti N+1).
     * - Loop karyawan × tanggal melewati setiap hari; kunci disamakan dengan
     *   keyBy di atas sehingga pengecekan cukup `isset`.
     * - Hari Minggu dilewati karena tidak ada absensi di hari libur.
     * - Nama karyawan di-pluck sekaligus untuk pesan yang mudah dibaca.
     *
     * @param  array<int, string>  $employeeIds  Array nilai employee_code
     * @param  Carbon              $startDate    Tanggal mulai (inklusif)
     * @param  Carbon              $endDate      Tanggal akhir (inklusif)
     * @return array<int, string>  Array deskripsi duplikat yang mudah dibaca
     */
    public function findDuplicates(array $employeeIds, Carbon $startDate, Carbon $endDate): array
    {
        $existingRecords = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->get(['id', 'employee_id', 'attendance_date', 'status'])
            ->keyBy(fn ($record) => $record->employee_id . '_' . Carbon::parse($record->attendance_date)->format('Y-m-d'));

        $duplicates = [];
        $employeeNames = Employee::whereIn('employee_code', $employeeIds)
            ->pluck('name', 'employee_code')
            ->toArray();

        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                if ($currentDate->dayOfWeek === Carbon::SUNDAY) {
                    $currentDate->addDay();
                    continue;
                }

                $key = $employeeId . '_' . $currentDate->format('Y-m-d');

                if (isset($existingRecords[$key])) {
                    $record = $existingRecords[$key];
                    $duplicates[] = sprintf(
                        '%s pada tanggal %s (Status: %s)',
                        $employeeNames[$employeeId] ?? $employeeId,
                        $currentDate->format('d-m-Y'),
                        $record->status
                    );
                }

                $currentDate->addDay();
            }
        }

        return $duplicates;
    }

    /**
     * Membuat data absensi secara massal untuk beberapa karyawan dalam rentang tanggal.
     *
     * Logika:
     * - Nested loop karyawan × tanggal; `$currentDate` di-copy dari startDate
     *   tiap karyawan lalu digeser per hari sampai melewati endDate.
     * - Hari Minggu DILEWATI (skip) karena Minggu adalah hari libur — tidak
     *   ada absensi di hari Minggu.
     * - Setiap record dibuat terpisah (bukan bulk insert) agar created_by dan
     *   nilai per record konsisten; jumlah total dikembalikan untuk pesan sukses.
     * - Catatan: diasumsikan caller sudah menjalankan findDuplicates() lebih dulu.
     *
     * @param  array<int, string>  $employeeIds  Array nilai employee_code
     * @param  Carbon              $startDate    Tanggal mulai (inklusif)
     * @param  Carbon              $endDate      Tanggal akhir (inklusif)
     * @param  string              $status       Status absensi (hadir|izin|sakit|cuti)
     * @param  string|null         $notes        Catatan opsional
     * @return int                 Jumlah data yang dimasukkan
     */
    public function bulkCreate(array $employeeIds, Carbon $startDate, Carbon $endDate, string $status, ?string $notes): int
    {
        // Kunci data: absensi pada periode yang payroll-nya sudah dibayar
        // tidak boleh ditambah (kecuali karyawan baru yang belum masuk payroll
        // paid periode itu — tidak memiliki payroll paid → tidak terkunci).
        $this->assertRangeNotLocked($employeeIds, $startDate, $endDate);

        $totalInserted = 0;

        foreach ($employeeIds as $employeeId) {
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                    Attendance::create([
                        'employee_id' => $employeeId,
                        'attendance_date' => $currentDate->format('Y-m-d'),
                        'status' => $status,
                        'notes' => $notes,
                        'created_by' => auth()->id(),
                    ]);
                    $totalInserted++;
                }
                $currentDate->addDay();
            }
        }

        foreach ($employeeIds as $employeeId) {
            $this->payrollService->recalculateForAttendanceRange($employeeId, $startDate, $endDate);
        }

        return $totalInserted;
    }

    /**
     * Memperbarui satu data absensi.
     *
     * Setelah perubahan, payroll draft yang periodenya menimpa tanggal absensi
     * (lama dan/atau baru) dihitung ulang agar snapshot payroll tetap sinkron.
     *
     * @param  Attendance  $attendance  Instance model absensi
     * @param  array       $data        Data pembaruan yang sudah divalidasi
     * @return bool
     */
    public function updateAttendance(Attendance $attendance, array $data): bool
    {
        $oldEmployeeId = $attendance->employee_id;
        $oldDate = Carbon::parse($attendance->attendance_date)->format('Y-m-d');

        // Kunci data: absensi pada periode yang payroll-nya sudah dibayar tidak
        // boleh diubah (kecuali karyawan baru yang belum masuk payroll paid).
        $newEmployeeId = $data['employee_id'] ?? $oldEmployeeId;
        $newDate = isset($data['attendance_date'])
            ? Carbon::parse($data['attendance_date'])->format('Y-m-d')
            : $oldDate;

        $this->assertAttendanceNotLocked($oldEmployeeId, $oldDate);
        $this->assertAttendanceNotLocked($newEmployeeId, $newDate);

        $result = $attendance->update($data);

        $fresh = $attendance->fresh();
        $newEmployeeId = $fresh->employee_id;
        $newDate = Carbon::parse($fresh->attendance_date)->format('Y-m-d');

        $this->recalculateAttendancePayroll($oldEmployeeId, $oldDate);

        if ($newEmployeeId !== $oldEmployeeId || $newDate !== $oldDate) {
            $this->recalculateAttendancePayroll($newEmployeeId, $newDate);
        }

        return $result;
    }

    /**
     * Menghapus data absensi berdasarkan ID-nya.
     *
     * Sebelum dihapus, record yang terdampak dicatat lalu setelah penghapusan
     * payroll draft yang periodenya menimpa tanggal tersebut dihitung ulang.
     *
     * @param  array<int, int>  $ids  Array ID absensi
     * @return int                    Jumlah data yang dihapus
     */
    public function deleteAttendances(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $attendances = Attendance::whereIn('id', $ids)->get(['employee_id', 'attendance_date']);

        // Kunci data: absensi pada periode yang payroll-nya sudah dibayar tidak
        // boleh dihapus (kecuali karyawan baru yang belum masuk payroll paid).
        foreach ($attendances as $attendance) {
            if ($attendance->employee_id && $attendance->attendance_date) {
                $this->assertAttendanceNotLocked($attendance->employee_id, $attendance->attendance_date);
            }
        }

        $deleted = Attendance::whereIn('id', $ids)->delete();

        $affectedDatesByEmployee = [];
        foreach ($attendances as $attendance) {
            $employeeId = $attendance->employee_id;
            $date = Carbon::parse($attendance->attendance_date)->format('Y-m-d');

            if ($employeeId && $date) {
                $affectedDatesByEmployee[$employeeId][] = $date;
            }
        }

        foreach ($affectedDatesByEmployee as $employeeId => $dates) {
            $this->payrollService->recalculateForAttendanceRange(
                $employeeId,
                Carbon::parse(min($dates)),
                Carbon::parse(max($dates))
            );
        }

        return $deleted;
    }

    /**
     * Melempar DomainException jika ada karyawan yang rentang absensinya
     * menimpa payroll PAID (data terkunci).
     *
     * @param  array<int, string>  $employeeIds  Kode karyawan
     * @param  Carbon              $startDate    Awal rentang (inklusif)
     * @param  Carbon              $endDate      Akhir rentang (inklusif)
     * @return void
     *
     * @throws \DomainException
     */
    private function assertRangeNotLocked(array $employeeIds, Carbon $startDate, Carbon $endDate): void
    {
        $lockedEmployees = [];

        foreach ($employeeIds as $employeeId) {
            $locking = $this->payrollService->findLockingPayroll($employeeId, $startDate, $endDate);

            if ($locking) {
                $employee = Employee::find($employeeId);
                $lockedEmployees[] = sprintf(
                    '%s (%s) — periode %s',
                    $employee?->name ?: $employeeId,
                    $employeeId,
                    $locking->formatted_period
                );
            }
        }

        if (count($lockedEmployees) > 0) {
            $display = implode('; ', array_slice($lockedEmployees, 0, 5));

            if (count($lockedEmployees) > 5) {
                $display .= sprintf(' dan %d lainnya', count($lockedEmployees) - 5);
            }

            throw new \DomainException(
                'Data absensi terkunci karena payroll periode tersebut sudah dibayar untuk: '.$display.'. '
                .'Hapus payroll paid terkait terlebih dahulu untuk membuka kunci dan mengubah data periode ini. '
                .'Karyawan baru yang belum masuk payroll paid periode ini tetap dapat diisi.'
            );
        }
    }

    /**
     * Melempar DomainException jika absensi karyawan pada satu tanggal menimpa
     * payroll PAID (data terkunci).
     *
     * @param  string  $employeeCode  Kode karyawan
     * @param  string  $date          Tanggal absensi (Y-m-d)
     * @return void
     *
     * @throws \DomainException
     */
    private function assertAttendanceNotLocked(string $employeeCode, string $date): void
    {
        $dateCarbon = Carbon::parse($date);

        $locking = $this->payrollService->findLockingPayroll($employeeCode, $dateCarbon, $dateCarbon);

        if ($locking) {
            $employee = Employee::find($employeeCode);
            $name = $employee?->name ?: $employeeCode;

            throw new \DomainException(sprintf(
                'Data absensi %s (%s) pada tanggal %s terkunci karena payroll periode %s sudah dibayar (status: paid). '
                .'Hapus payroll paid terkait untuk membuka kunci dan mengubah data periode ini. '
                .'Karyawan baru yang belum masuk payroll paid periode ini tetap dapat diisi.',
                $name,
                $employeeCode,
                $dateCarbon->format('d-m-Y'),
                $locking->formatted_period
            ));
        }
    }

    /**
     * Menghitung ulang payroll draft yang periodenya memuat tanggal absensi.
     *
     * @param  string|null  $employeeId      Kode karyawan
     * @param  string|null  $attendanceDate  Tanggal absensi (Y-m-d)
     * @return void
     */
    private function recalculateAttendancePayroll(?string $employeeId, ?string $attendanceDate): void
    {
        if (!$employeeId || !$attendanceDate) {
            return;
        }

        $date = Carbon::parse($attendanceDate);

        $this->payrollService->recalculateForAttendanceRange($employeeId, $date, $date);
    }

    /**
     * Membuat pesan sukses yang mudah dibaca untuk pembuatan massal.
     *
     * Logika:
     * - totalDays = jumlah hari kerja dalam rentang (Senin-Sabtu, Minggu
     *   dikecualikan karena hari libur).
     * - Memakai sprintf agar format nominal pesan konsisten dan terhindar dari
     *   concatenation yang berantakan.
     *
     * @param  int     $totalInserted  Jumlah data yang dimasukkan
     * @param  int     $employeeCount  Jumlah karyawan
     * @param  Carbon  $startDate      Tanggal mulai
     * @param  Carbon  $endDate        Tanggal akhir
     * @return string
     */
    public function buildBulkCreateMessage(int $totalInserted, int $employeeCount, Carbon $startDate, Carbon $endDate): string
    {
        $totalDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                $totalDays++;
            }
            $currentDate->addDay();
        }

        return sprintf(
            'Berhasil menambahkan %d record absensi untuk %d karyawan selama %d hari kerja (%s s/d %s).',
            $totalInserted,
            $employeeCount,
            $totalDays,
            $startDate->format('d-m-Y'),
            $endDate->format('d-m-Y')
        );
    }

    /**
     * Membuat pesan kesalahan yang mudah dibaca untuk data duplikat.
     *
     * Membatasi tampilan hingga 5 duplikat pertama dan menambahkan jumlah item yang tersisa.
     *
     * @param  array<int, string>  $duplicates  Array deskripsi duplikat
     * @return string
     */
    public function buildDuplicateErrorMessage(array $duplicates): string
    {
        $errorMessage = 'Karyawan berikut sudah memiliki absensi: ';
        $displayDuplicates = array_slice($duplicates, 0, 5);
        $errorMessage .= implode('; ', $displayDuplicates);

        if (count($duplicates) > 5) {
            $errorMessage .= sprintf(' dan %d lainnya', count($duplicates) - 5);
        }

        $errorMessage .= '. Silakan hapus atau edit data yang sudah ada.';

        return $errorMessage;
    }
}
