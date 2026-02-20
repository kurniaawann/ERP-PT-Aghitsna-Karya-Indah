<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_month',
        'period_year',
        'period_type',
        'week_number',
        'project_name', // Nama proyek (opsional)
        'base_salary',
        'total_work_days',
        'present_days',
        'permission_days', // Izin
        'sick_days', // Sakit
        'leave_days', // Cuti
        'overtime_days', // Lembur
        'deduction_amount',
        'overtime_total',
        'kasbon_deduction',
        'additional_expenses',
        'additional_expenses_notes',
        'net_salary',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'week_number' => 'integer',
        'base_salary' => 'integer',
        'total_work_days' => 'integer',
        'present_days' => 'integer',
        'permission_days' => 'integer',
        'sick_days' => 'integer',
        'leave_days' => 'integer',
        'overtime_days' => 'integer',
        'deduction_amount' => 'integer',
        'overtime_total' => 'integer',
        'kasbon_deduction' => 'integer',
        'additional_expenses' => 'integer',
        'net_salary' => 'integer',
        'payment_date' => 'date',
    ];

    /**
     * Relasi ke Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    /**
     * Relasi ke Kasbon yang dipotong di payroll ini
     */
    public function kasbons()
    {
        return $this->hasMany(Kasbon::class, 'deducted_in_payroll_id');
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, $month, $year, $weekNumber = null)
    {
        $query->where('period_month', $month)
            ->where('period_year', $year);

        if ($weekNumber !== null) {
            $query->where('week_number', $weekNumber);
        }

        return $query;
    }

    /**
     * Scope untuk filter payroll mingguan
     */
    public function scopeWeekly($query)
    {
        return $query->where('period_type', 'weekly');
    }

    /**
     * Check apakah sudah dibayar
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Get formatted period (Nov 2025)
     */
    public function getFormattedPeriodAttribute()
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $period = $months[$this->period_month] . ' ' . $this->period_year;

        if ($this->period_type === 'weekly' && $this->week_number) {
            $period .= ' - Minggu ' . $this->week_number;
        }

        return $period;
    }

    /**
     * Calculate net salary dengan kasbon deduction
     */
    public function calculateNetSalary()
    {
        // Base calculation: upah kotor - potongan + lembur
        $netSalary = $this->base_salary - $this->deduction_amount + $this->overtime_total;

        // Kurangi kasbon jika ada
        if ($this->kasbon_deduction) {
            $netSalary -= $this->kasbon_deduction;
        }

        // Tambah additional expenses (token listrik/air, dll) - ini pengeluaran PT
        // Tidak mengurangi net salary karena ini benefit untuk karyawan

        return $netSalary;
    }

    /**
     * Get total payment (net salary + additional expenses)
     */
    public function getTotalPaymentAttribute()
    {
        return $this->net_salary + ($this->additional_expenses ?? 0);
    }
}
