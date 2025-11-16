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
        'base_salary',
        'total_work_days',
        'present_days',
        'permission_days', // Izin
        'sick_days', // Sakit
        'leave_days', // Cuti
        'overtime_days', // Lembur
        'deduction_amount',
        'overtime_total',
        'net_salary',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'base_salary' => 'integer',
        'total_work_days' => 'integer',
        'present_days' => 'integer',
        'permission_days' => 'integer',
        'sick_days' => 'integer',
        'leave_days' => 'integer',
        'overtime_days' => 'integer',
        'deduction_amount' => 'integer',
        'overtime_total' => 'integer',
        'net_salary' => 'integer',
        'payment_date' => 'date',
    ];

    /**
     * Relasi ke Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, $month, $year)
    {
        return $query->where('period_month', $month)
            ->where('period_year', $year);
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

        return $months[$this->period_month] . ' ' . $this->period_year;
    }
}
