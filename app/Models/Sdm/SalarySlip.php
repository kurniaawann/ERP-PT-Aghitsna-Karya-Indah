<?php

namespace App\Models\Sdm;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel salary_slips (Slip Gaji Karyawan Bulanan).
 *
 * Satu baris = satu slip gaji per karyawan per bulan. Menyimpan snapshot
 * perhitungan (gaji pokok, rekap absensi, potongan, gaji bersih) serta
 * tanda tangan petinggi agar slip tetap konsisten dan bisa dicetak ulang.
 *
 * @property int    $id
 * @property string $employee_code
 * @property int    $period_month
 * @property int    $period_year
 * @property int    $base_salary
 * @property int    $transport_rate
 * @property int    $meal_rate
 * @property int    $ump
 * @property int    $work_days
 * @property int    $present_days
 * @property int    $permission_days
 * @property int    $sick_days
 * @property int    $leave_days
 * @property int    $absent_days
 * @property int    $libur_days
 * @property array|null $attendance_detail  Matriks absensi { "1": "H", ... }
 * @property int    $daily_rate
 * @property int    $salary_deduction
 * @property int    $transport_total
 * @property int    $meal_total
 * @property int    $total_income
 * @property int    $bpjs_kesehatan_employee
 * @property int    $jht_employee
 * @property int    $jpn_employee
 * @property int    $pph21
 * @property int    $kasbon_deduction
 * @property int    $total_deduction
 * @property int    $bpjs_kesehatan_company
 * @property int    $jht_company
 * @property int    $jkk_company
 * @property int    $jkm_company
 * @property int    $net_salary
 * @property \Carbon\Carbon|null $payment_date
 * @property string $status  'draft' | 'paid'
 * @property string|null $notes
 * @property array|null $signatures
 */
class SalarySlip extends Model
{
    use HasFactory;

    /** Kode status absensi pada matriks attendance_detail. */
    public const DAY_PRESENT = 'H';
    public const DAY_PERMISSION = 'I';
    public const DAY_SICK = 'S';
    public const DAY_LEAVE = 'C';
    public const DAY_ABSENT = 'A';
    public const DAY_LIBUR = 'L';

    protected $table = 'salary_slips';

    protected $fillable = [
        'employee_code',
        'period_month',
        'period_year',
        'base_salary',
        'transport_rate',
        'meal_rate',
        'ump',
        'work_days',
        'present_days',
        'permission_days',
        'sick_days',
        'leave_days',
        'absent_days',
        'libur_days',
        'attendance_detail',
        'daily_rate',
        'salary_deduction',
        'transport_total',
        'meal_total',
        'total_income',
        'bpjs_kesehatan_employee',
        'jht_employee',
        'jpn_employee',
        'pph21',
        'kasbon_deduction',
        'total_deduction',
        'bpjs_kesehatan_company',
        'jht_company',
        'jkk_company',
        'jkm_company',
        'net_salary',
        'payment_date',
        'status',
        'notes',
        'signatures',
        'created_by',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'base_salary' => 'integer',
        'transport_rate' => 'integer',
        'meal_rate' => 'integer',
        'ump' => 'integer',
        'work_days' => 'integer',
        'present_days' => 'integer',
        'permission_days' => 'integer',
        'sick_days' => 'integer',
        'leave_days' => 'integer',
        'absent_days' => 'integer',
        'libur_days' => 'integer',
        'attendance_detail' => 'array',
        'daily_rate' => 'integer',
        'salary_deduction' => 'integer',
        'transport_total' => 'integer',
        'meal_total' => 'integer',
        'total_income' => 'integer',
        'bpjs_kesehatan_employee' => 'integer',
        'jht_employee' => 'integer',
        'jpn_employee' => 'integer',
        'pph21' => 'integer',
        'kasbon_deduction' => 'integer',
        'total_deduction' => 'integer',
        'bpjs_kesehatan_company' => 'integer',
        'jht_company' => 'integer',
        'jkk_company' => 'integer',
        'jkm_company' => 'integer',
        'net_salary' => 'integer',
        'payment_date' => 'date',
        'signatures' => 'array',
    ];

    /**
     * Karyawan pemilik slip gaji ini.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'employee_code');
    }

    /**
     * Label periode slip, contoh: "Agustus 2026".
     */
    public function getFormattedPeriodAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return ($months[$this->period_month] ?? $this->period_month).' '.$this->period_year;
    }

    /**
     * Jumlah hari kalender pada periode slip.
     */
    public function getDaysInMonthAttribute(): int
    {
        if ($this->period_year && $this->period_month) {
            return Carbon::createFromDate($this->period_year, $this->period_month, 1)->daysInMonth;
        }

        return 30;
    }

    /**
     * Mendapatkan matriks absensi lengkap (kunci 1..hari-dalam-bulan).
     *
     * Hari tanpa status dianggap "belum diisi" (null) sehingga tampilan bisa
     * membedakan hari kosong dengan status Hadir.
     *
     * @return array<int, string|null>
     */
    public function getAttendanceMatrixAttribute(): array
    {
        $detail = is_array($this->attendance_detail) ? $this->attendance_detail : [];
        $matrix = [];

        for ($day = 1; $day <= $this->days_in_month; $day++) {
            $matrix[$day] = isset($detail[$day]) ? (string) $detail[$day] : null;
        }

        return $matrix;
    }

    /**
     * Apakah slip sudah dibayar (terkunci).
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
