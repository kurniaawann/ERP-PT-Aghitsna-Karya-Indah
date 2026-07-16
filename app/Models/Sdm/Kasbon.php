<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kasbon extends Model
{
    use HasFactory;

    protected $primaryKey = 'kasbon_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kasbon_code',
        'employee_id',
        'kasbon_type',
        'division',
        'amount',
        'kasbon_date',
        'week_number',
        'period_month',
        'period_year',
        'period_start_date',
        'period_end_date',
        'status',
        'deducted_in_payroll_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'week_number' => 'integer',
        'period_month' => 'integer',
        'period_year' => 'integer',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'kasbon_date' => 'date',
    ];

    /**
     * Generate kode kasbon berikutnya (KSB001, KSB002, dst)
     */
    public static function generateKasbonCode()
    {
        $lastKasbon = self::orderBy('kasbon_code', 'desc')->first();

        if (!$lastKasbon) {
            return 'KSB001';
        }

        // Ambil nomor dari kode terakhir (contoh: KSB001 -> 001)
        $lastNumber = (int) substr($lastKasbon->kasbon_code, 3);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'KSB' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Relasi ke Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    /**
     * Relasi ke Payroll (saat kasbon sudah dipotong)
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'deducted_in_payroll_id');
    }

    /**
     * Scope untuk filter kasbon yang belum dipotong (pending)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope untuk filter kasbon yang sudah dipotong (deducted)
     */
    public function scopeDeducted($query)
    {
        return $query->where('status', 'deducted');
    }

    /**
     * Scope untuk filter berdasarkan periode menggunakan period_start_date
     */
    public function scopeForPeriod($query, $month, $year)
    {
        return $query->whereMonth('period_start_date', $month)
            ->whereYear('period_start_date', $year);
    }

    /**
     * Scope untuk filter berdasarkan period_start_date
     */
    public function scopeForStartDate($query, $startDate)
    {
        return $query->where('period_start_date', $startDate instanceof \Carbon\Carbon
            ? $startDate->format('Y-m-d')
            : $startDate);
    }

    /**
     * Scope untuk kasbon personal (per orang)
     */
    public function scopePersonal($query)
    {
        return $query->where('kasbon_type', 'personal');
    }

    /**
     * Scope untuk kasbon team (per tim)
     */
    public function scopeTeam($query)
    {
        return $query->where('kasbon_type', 'team');
    }

    /**
     * Get total kasbon untuk employee tertentu dalam periode tertentu
     */
    public static function getTotalForEmployee($employeeId, $periodStartDate)
    {
        $startDate = $periodStartDate instanceof \Carbon\Carbon
            ? $periodStartDate->format('Y-m-d')
            : $periodStartDate;

        return self::where('employee_id', $employeeId)
            ->where('period_start_date', $startDate)
            ->pending()
            ->sum('amount');
    }

    /**
     * Get total kasbon team dalam periode tertentu
     */
    public static function getTotalTeamKasbon($periodStartDate)
    {
        $startDate = $periodStartDate instanceof \Carbon\Carbon
            ? $periodStartDate->format('Y-m-d')
            : $periodStartDate;

        return self::where('kasbon_type', 'team')
            ->where('period_start_date', $startDate)
            ->pending()
            ->sum('amount');
    }

    /**
     * Mark kasbon sebagai sudah dipotong
     */
    public function markAsDeducted($payrollId)
    {
        $this->status = 'deducted';
        $this->deducted_in_payroll_id = $payrollId;
        $this->save();
    }

    /**
     * Format amount untuk display
     */
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return $this->status === 'pending' ? 'Belum Dipotong' : 'Sudah Dipotong';
    }

    /**
     * Get kasbon type label
     */
    public function getKasbonTypeLabelAttribute()
    {
        return $this->kasbon_type === 'personal' ? 'Per Orang' : 'Per Tim';
    }
}
