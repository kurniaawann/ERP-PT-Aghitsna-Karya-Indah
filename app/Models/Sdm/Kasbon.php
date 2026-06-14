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
        'employee_details',
        'amount',
        'remaining_amount',
        'kasbon_date',
        'week_number',
        'period_month',
        'period_year',
        'status',
        'deducted_in_payroll_id',
        'notes',
    ];

    protected $casts = [
        'employee_details' => 'array',
        'amount' => 'integer',
        'remaining_amount' => 'integer',
        'week_number' => 'integer',
        'period_month' => 'integer',
        'period_year' => 'integer',
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
     * Relasi ke log pemotongan kasbon
     */
    public function deductionLogs()
    {
        return $this->hasMany(KasbonDeductionLog::class, 'kasbon_code', 'kasbon_code');
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
     * Scope untuk kasbon team yang masih memiliki sisa (active)
     */
    public function scopeActive($query)
    {
        return $query->where('remaining_amount', '>', 0);
    }

    /**
     * Get total kasbon untuk employee tertentu dalam periode tertentu
     */
    public static function getTotalForEmployee($employeeId, $month, $year, $weekNumber = null)
    {
        $query = self::where('employee_id', $employeeId)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->pending();

        if ($weekNumber !== null) {
            $query->where('week_number', $weekNumber);
        }

        return $query->sum('amount');
    }

    /**
     * Get total kasbon team yang masih aktif (belum lunas)
     */
    public static function getTotalActiveTeamKasbon()
    {
        return self::where('kasbon_type', 'team')
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');
    }

    /**
     * Mark kasbon sebagai sudah dipotong
     */
    public function markAsDeducted($payrollId)
    {
        $this->status = 'deducted';
        $this->deducted_in_payroll_id = $payrollId;
        $this->remaining_amount = 0;
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
     * Format remaining_amount untuk display
     */
    public function getFormattedRemainingAttribute()
    {
        return 'Rp ' . number_format($this->remaining_amount ?? 0, 0, ',', '.');
    }

    /**
     * Get total yang sudah terpotong
     */
    public function getTotalDeductedAttribute()
    {
        return $this->amount - ($this->remaining_amount ?? 0);
    }

    /**
     * Format total deducted untuk display
     */
    public function getFormattedTotalDeductedAttribute()
    {
        return 'Rp ' . number_format(max(0, $this->total_deducted), 0, ',', '.');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        if ($this->status === 'deducted') return 'Lunas';
        if ($this->remaining_amount > 0 && $this->remaining_amount < $this->amount) return 'Belum Lunas';
        return 'Belum Dipotong';
    }

    /**
     * Get kasbon type label
     */
    public function getKasbonTypeLabelAttribute()
    {
        return $this->kasbon_type === 'personal' ? 'Per Orang' : 'Per Tim';
    }

    /**
     * Status CSS class
     */
    public function getStatusClassAttribute()
    {
        if ($this->status === 'deducted') return 'bg-success-light text-success';
        if ($this->remaining_amount > 0 && $this->remaining_amount < $this->amount) return 'bg-warning-light text-warning';
        return 'bg-info-light text-info';
    }
}
