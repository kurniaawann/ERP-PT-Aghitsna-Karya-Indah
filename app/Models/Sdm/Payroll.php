<?php

namespace App\Models\Sdm;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent model for employee payroll records.
 *
 * Stores weekly payroll data for daily workers including
 * attendance summary, salary calculation, kasbon deductions,
 * and net salary.
 *
 * Status flow: draft → paid
 *
 * Period identification uses period_start_date as the primary key.
 * Each Monday-Saturday week has a unique period_start_date per employee.
 *
 * @property int    $id
 * @property string $employee_id          Employee code (FK to employees.employee_code)
 * @property int    $period_month         Payroll month (1-12) - derived from period_start_date
 * @property int    $period_year          Payroll year - derived from period_start_date
 * @property string $period_type          Period type ('weekly')
 * @property int    $week_number          Week number within the month (1-N)
 * @property string $period_start_date    Start date of the pay period (YYYY-MM-DD)
 * @property string $period_end_date      End date of the pay period (YYYY-MM-DD)
 * @property string|null $project_name    Project name (optional)
 * @property int    $base_salary          Daily wage rate
 * @property int    $total_work_days      Total working days in the period
 * @property int    $present_days         Days employee was present
 * @property int    $permission_days      Days with permission (izin)
 * @property int    $sick_days            Days with sick leave
 * @property int    $leave_days           Days with annual leave
 * @property int    $overtime_days        Days with overtime
 * @property int    $deduction_amount     Additional deductions (fixed)
 * @property int    $overtime_total       Total overtime pay
 * @property int    $kasbon_deduction     Total kasbon deduction (personal + team)
 * @property int    $net_salary           Final salary after all deductions
 * @property \Carbon\Carbon|null $payment_date  Payment date
 * @property string $status               'draft' or 'paid'
 * @property string|null $notes           Optional notes
 * @property array|null $signatures       Snapshot petinggi untuk blok tanda tangan
 *                                        (disetujui/diperiksa/dibuat berisi id,
 *                                        name, position, signature_image)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Payroll extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'period_month',
        'period_year',
        'period_type',
        'week_number',
        'period_start_date',
        'period_end_date',
        'project_name',
        'base_salary',
        'total_work_days',
        'present_days',
        'permission_days',
        'sick_days',
        'leave_days',
        'overtime_days',
        'deduction_amount',
        'overtime_total',
        'kasbon_deduction',
        'net_salary',
        'payment_date',
        'status',
        'notes',
        'signatures',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'week_number' => 'integer',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
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
        'net_salary' => 'integer',
        'payment_date' => 'date',
        'signatures' => 'array',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the employee that owns this payroll.
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    /**
     * Get the kasbons deducted in this payroll.
     *
     * @return HasMany
     */
    public function kasbons(): HasMany
    {
        return $this->hasMany(Kasbon::class, 'deducted_in_payroll_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to filter payroll by period_start_date month and year.
     *
     * @param  Builder       $query
     * @param  int           $month
     * @param  int           $year
     * @return Builder
     */
    public function scopeForPeriod(Builder $query, int $month, int $year): Builder
    {
        return $query->whereMonth('period_start_date', $month)
            ->whereYear('period_start_date', $year);
    }

    /**
     * Scope to filter weekly payroll only.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly');
    }

    // ========================================
    // ACCESSORS & METHODS
    // ========================================

    /**
     * Check if this payroll has been paid.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Get formatted period string using date range.
     *
     * Examples:
     *   "1 - 4 Feb 2026" (same month)
     *   "26 Feb - 3 Mar 2026" (cross-month)
     *
     * Falls back to old format for legacy data without period_start_date.
     *
     * @return string
     */
    public function getFormattedPeriodAttribute(): string
    {
        if ($this->period_start_date && $this->period_end_date) {
            $start = Carbon::parse($this->period_start_date);
            $end = Carbon::parse($this->period_end_date);

            if ($start->month === $end->month && $start->year === $end->year) {
                return $start->format('d') . ' - ' . $end->format('d M Y');
            }

            if ($start->year === $end->year) {
                return $start->format('d M') . ' - ' . $end->format('d M Y');
            }

            return $start->format('d M Y') . ' - ' . $end->format('d M Y');
        }

        // Fallback for legacy data
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $period = $months[$this->period_month] . ' ' . $this->period_year;

        if ($this->period_type === 'weekly' && $this->week_number) {
            $period .= ' - Minggu ' . $this->week_number;
        }

        return $period;
    }

    /**
     * Calculate net salary from stored components.
     *
     * Formula: base_salary - deduction_amount + overtime_total - kasbon_deduction
     *
     * @return int
     */
    public function calculateNetSalary(): int
    {
        $netSalary = $this->base_salary - $this->deduction_amount + $this->overtime_total;

        if ($this->kasbon_deduction) {
            $netSalary -= $this->kasbon_deduction;
        }

        return $netSalary;
    }
}
