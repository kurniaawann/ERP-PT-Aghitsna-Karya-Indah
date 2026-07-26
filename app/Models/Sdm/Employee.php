<?php

namespace App\Models\Sdm;

use App\Models\User;
use App\Services\Sdm\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel employees.
 *
 * Menggunakan employee_code (string) sebagai primary key.
 * Setiap karyawan memiliki relasi ke Attendance, Payroll, dan Kasbon.
 *
 * @property string $employee_code
 * @property string $name
 * @property string|null $position
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $division
 * @property int|null $base_salary
 * @property int|null $daily_wage
 * @property Carbon|null $join_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read int $effective_wage
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sdm\Attendance[] $attendances
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sdm\Payroll[] $payrolls
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sdm\Kasbon[] $kasbons
 */
class Employee extends Model
{
    use HasFactory;

    /**
     * Primary key untuk model ini.
     *
     * @var string
     */
    protected $primaryKey = 'employee_code';

    /**
     * Menunjukkan apakah ID menggunakan auto-increment.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Tipe data dari auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_code',
        'name',
        'position',
        'phone',
        'email',
        'address',
        'division',
        'base_salary',
        'daily_wage',
        'join_date',
        'created_by',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'join_date' => 'date',
        'base_salary' => 'integer',
        'daily_wage' => 'integer',
    ];

    /**
     * Membuat kode karyawan berikutnya secara berurutan.
     *
     * Format: EMP001, EMP002, ..., EMPnnn.
     *
     * @return string
     */
    public static function generateEmployeeCode(): string
    {
        $lastEmployee = self::orderBy('employee_code', 'desc')->first();

        if (!$lastEmployee) {
            return 'EMP001';
        }

        $lastNumber = (int) substr($lastEmployee->employee_code, 3);
        $newNumber = $lastNumber + 1;

        return 'EMP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Mendapatkan data absensi untuk karyawan ini.
     *
     * @return HasMany
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_code');
    }

    /**
     * Mendapatkan data payroll untuk karyawan ini.
     *
     * @return HasMany
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id', 'employee_code');
    }

    /**
     * Mendapatkan data kasbon untuk karyawan ini.
     *
     * @return HasMany
     */
    public function kasbons(): HasMany
    {
        return $this->hasMany(Kasbon::class, 'employee_id', 'employee_code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mendapatkan upah efektif (daily_wage atau base_salary sebagai cadangan).
     *
     * @return int|null
     */
    public function getEffectiveWageAttribute(): int|null
    {
        return $this->daily_wage ?? $this->base_salary;
    }

    /**
     * Menghitung total upah berdasarkan jumlah hari kerja.
     *
     * @param  int  $daysWorked
     * @return int
     */
    public function calculateWage(int $daysWorked): int
    {
        return $this->effective_wage * $daysWorked;
    }

    /**
     * Mendeteksi nomor minggu untuk tanggal tertentu menggunakan minggu Senin-Sabtu.
     *
     * Setiap minggu berjalan dari Senin sampai Sabtu. Jika bulan tidak dimulai
     * pada hari Senin, hari-hari sebelum Senin pertama membentuk "Minggu 1" parsial.
     * Minggu selalu dikecualikan sebagai hari non-kerja.
     *
     * Contoh untuk Juli 2026 (1 Jul = Rabu):
     *   Minggu 1: Jul 1-4   (Rab-Sab)
     *   Minggu 2: Jul 6-11  (Sen-Sab)
     *   Minggu 5: Jul 27-31 (Sen-Jum)
     *
     * @param  \Carbon\Carbon|string  $date
     * @return int  Nomor minggu (1-N, tergantung bulan)
     */
    public static function detectWeekNumber($date): int
    {
        $parsed = Carbon::parse($date);
        $weeks = PayrollService::getWeeksInMonth($parsed->year, $parsed->month);

        foreach ($weeks as $week) {
            if ($parsed->gte($week['start']) && $parsed->lte($week['end'])) {
                return $week['week_number'];
            }
        }

        // Cadangan: jika tanggal tidak ada dalam minggu mana pun (misalnya Minggu di batas bulan),
        // kembalikan nomor minggu terakhir
        return end($weeks)['week_number'] ?? 1;
    }

    /**
     * Mengecek apakah payroll untuk periode tertentu (berdasarkan tanggal mulai) sudah dibayar.
     *
     * @param  Carbon|string  $periodStartDate
     * @return bool
     */
    public function isPayrollPaidByStartDate($periodStartDate): bool
    {
        $startDate = $periodStartDate instanceof Carbon
            ? $periodStartDate->format('Y-m-d')
            : $periodStartDate;

        return $this->payrolls()
            ->where('period_start_date', $startDate)
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * Mengecek apakah payroll untuk minggu tertentu sudah dibayar (metode lama).
     *
     * @deprecated Gunakan isPayrollPaidByStartDate() sebagai pengganti
     *
     * @param  int  $month
     * @param  int  $year
     * @param  int  $weekNumber
     * @return bool
     */
    public function isPayrollPaid(int $month, int $year, int $weekNumber): bool
    {
        return $this->payrolls()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('week_number', $weekNumber)
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * Count attendance days from period start to the given kasbon date.
     *
     * @param  Carbon|string  $periodStart  Start date of the pay period
     * @param  Carbon|string  $kasbonDate   Date of the kasbon
     * @return int
     */
    public function getAttendanceUpToDate($periodStart, $kasbonDate): int
    {
        $startDate = $periodStart instanceof Carbon
            ? $periodStart->format('Y-m-d')
            : $periodStart;
        $endDate = Carbon::parse($kasbonDate)->format('Y-m-d');

        $count = $this->attendances()
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->whereNotIn('status', ['absent', 'alpha'])
            ->count();

        return $count;
    }

    /**
     * Calculate the maximum kasbon allowed up to the given date.
     *
     * @param  Carbon|string  $periodStart  Start date of the pay period
     * @param  Carbon|string  $kasbonDate   Date of the kasbon
     * @return int
     */
    public function getMaxKasbonUpToDate($periodStart, $kasbonDate): int
    {
        $daysWorked = $this->getAttendanceUpToDate($periodStart, $kasbonDate);
        $dailyWage = $this->daily_wage ?? $this->base_salary;
        return $dailyWage * $daysWorked;
    }

    /**
     * Determine if the given amount can be taken as kasbon.
     *
     * @param  int            $amount
     * @param  Carbon|string  $periodStart  Start date of the pay period
     * @param  Carbon|string  $kasbonDate   Date of the kasbon
     * @return bool
     */
    public function canTakeKasbon(int $amount, $periodStart, $kasbonDate): bool
    {
        $maxKasbon = $this->getMaxKasbonUpToDate($periodStart, $kasbonDate);
        return $amount <= $maxKasbon;
    }
}
