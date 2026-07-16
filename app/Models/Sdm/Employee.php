<?php

namespace App\Models\Sdm;

use App\Services\Sdm\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for the employees table.
 *
 * Uses employee_code (string) as the primary key.
 * Each employee has relations to Attendance, Payroll, and Kasbon.
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
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'employee_code';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'join_date' => 'date',
        'base_salary' => 'integer',
        'daily_wage' => 'integer',
    ];

    /**
     * Generate the next employee code in sequence.
     *
     * The format is EMP001, EMP002, ..., EMPnnn.
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
     * Get the attendances for the employee.
     *
     * @return HasMany
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'employee_code');
    }

    /**
     * Get the payrolls for the employee.
     *
     * @return HasMany
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id', 'employee_code');
    }

    /**
     * Get the kasbons for the employee.
     *
     * @return HasMany
     */
    public function kasbons(): HasMany
    {
        return $this->hasMany(Kasbon::class, 'employee_id', 'employee_code');
    }

    /**
     * Get the effective wage (daily_wage or base_salary as fallback).
     *
     * @return int|null
     */
    public function getEffectiveWageAttribute(): int|null
    {
        return $this->daily_wage ?? $this->base_salary;
    }

    /**
     * Calculate total wage based on the number of days worked.
     *
     * @param  int  $daysWorked
     * @return int
     */
    public function calculateWage(int $daysWorked): int
    {
        return $this->effective_wage * $daysWorked;
    }

    /**
     * Detect the week number for a given date using Monday-Saturday weeks.
     *
     * Each week runs from Monday to Saturday. If the month does not start
     * on Monday, the days before the first Monday form a partial "Week 1".
     * Sunday is always excluded as a non-working day.
     *
     * Example for July 2026 (1 Jul = Wednesday):
     *   Week 1: Jul 1-4   (Wed-Sat)
     *   Week 2: Jul 6-11  (Mon-Sat)
     *   Week 5: Jul 27-31 (Mon-Fri)
     *
     * @param  \Carbon\Carbon|string  $date
     * @return int  Week number (1-N, depends on month)
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

        // Fallback: if date is not in any week (e.g. Sunday at month boundary),
        // return the last week number
        return end($weeks)['week_number'] ?? 1;
    }

    /**
     * Check if the payroll for a specific period (by start date) is already paid.
     *
     * @param  Carbon|string  $periodStartDate
     * @return bool
     */
    public function isPayrollPaidByStartDate($periodStartDate): bool
    {
        $startDate = $periodStartDate instanceof \Carbon\Carbon
            ? $periodStartDate->format('Y-m-d')
            : $periodStartDate;

        return $this->payrolls()
            ->where('period_start_date', $startDate)
            ->where('status', 'paid')
            ->exists();
    }

    /**
     * Check if the payroll for a specific week is already paid (legacy method).
     *
     * @deprecated Use isPayrollPaidByStartDate() instead
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
        $startDate = $periodStart instanceof \Carbon\Carbon
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
