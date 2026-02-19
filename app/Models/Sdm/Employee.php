<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $primaryKey = 'employee_code';
    public $incrementing = false;
    protected $keyType = 'string';

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
    ];

    protected $casts = [
        'join_date' => 'date',
        'base_salary' => 'integer',
        'daily_wage' => 'integer',
    ];

    /**
     * Generate kode employee berikutnya (EMP001, EMP002, dst)
     */
    public static function generateEmployeeCode()
    {
        $lastEmployee = self::orderBy('employee_code', 'desc')->first();

        if (!$lastEmployee) {
            return 'EMP001';
        }

        // Ambil nomor dari kode terakhir (contoh: EMP001 -> 001)
        $lastNumber = (int) substr($lastEmployee->employee_code, 3);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'EMP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Relasi ke Attendance
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_code');
    }

    /**
     * Relasi ke Payroll
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'employee_id', 'employee_code');
    }

    /**
     * Relasi ke Kasbon
     */
    public function kasbons()
    {
        return $this->hasMany(Kasbon::class, 'employee_id', 'employee_code');
    }

    /**
     * Get upah yang digunakan (prioritas: daily_wage, jika tidak ada gunakan base_salary)
     */
    public function getEffectiveWageAttribute()
    {
        return $this->daily_wage ?? $this->base_salary;
    }

    /**
     * Calculate total upah berdasarkan hari masuk
     */
    public function calculateWage($daysWorked)
    {
        return $this->effective_wage * $daysWorked;
    }

    /**
     * Deteksi minggu ke berapa dari tanggal (1-4)
     * Minggu 1: 1-7, Minggu 2: 8-14, Minggu 3: 15-21, Minggu 4: 22-akhir bulan
     */
    public static function detectWeekNumber($date)
    {
        $day = \Carbon\Carbon::parse($date)->day;

        if ($day >= 1 && $day <= 7)
            return 1;
        if ($day >= 8 && $day <= 14)
            return 2;
        if ($day >= 15 && $day <= 21)
            return 3;
        return 4; // 22-akhir bulan
    }

    /**
     * Cek apakah payroll minggu tertentu sudah paid
     */
    public function isPayrollPaid($month, $year, $weekNumber)
    {
        return $this->payrolls()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('week_number', $weekNumber)
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * Hitung total hari kehadiran dari awal minggu sampai tanggal kasbon (hanya minggu tersebut)
     */
    public function getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate)
    {
        $startDay = (($weekNumber - 1) * 7) + 1;
        $startDate = \Carbon\Carbon::create($year, $month, $startDay)->format('Y-m-d');

        // Tanggal akhir = tanggal kasbon (pastikan format Y-m-d)
        $endDate = \Carbon\Carbon::parse($kasbonDate)->format('Y-m-d');

        // Hitung jumlah hari hadir (semua status kecuali absent/alpha)
        // Status yang dihitung: present, overtime, permission, sick, leave
        $count = $this->attendances()
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->whereNotIn('status', ['absent', 'alpha'])
            ->count();

        return $count;
    }

    /**
     * Hitung maksimal kasbon berdasarkan kehadiran sampai tanggal kasbon
     * Formula: Gaji per hari × Total hari hadir sampai tanggal kasbon
     */
    public function getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate)
    {
        $daysWorked = $this->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);
        $dailyWage = $this->daily_wage ?? $this->base_salary;

        return $dailyWage * $daysWorked;
    }

    /**
     * Cek apakah jumlah kasbon melebihi batas maksimal
     */
    public function canTakeKasbon($amount, $month, $year, $weekNumber, $kasbonDate)
    {
        $maxKasbon = $this->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);
        return $amount <= $maxKasbon;
    }
}
