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
}
