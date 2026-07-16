<?php

namespace App\Models\Sdm;

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
     * Detect the week number (1-4) for a given date.
     *
     * Weeks are defined as: 1-7 days, 8-14 days, 15-21 days, 22-end.
     *
     * @param  \Carbon\Carbon|string  $date
     * @return int
     */
    public static function detectWeekNumber($date): int
    {
        $day = Carbon::parse($date)->day;

        if ($day >= 1 && $day <= 7) return 1;
        if ($day >= 8 && $day <= 14) return 2;
        if ($day >= 15 && $day <= 21) return 3;

        return 4;
    }

    /**
     * Check if the payroll for a specific week is already paid.
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
     * Count attendance days up to the given kasbon date.
     *
     * @param  int  $month
     * @param  int  $year
     * @param  int  $weekNumber
     * @param  \Carbon\Carbon|string  $kasbonDate
     * @return int
     */
    public function getAttendanceUpToDate(int $month, int $year, int $weekNumber, $kasbonDate): int
    {
        $startDay = (($weekNumber - 1) * 7) + 1;
        $startDate = Carbon::create($year, $month, $startDay)->format('Y-m-d');
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
     * This is based on the daily wage and number of attendance days.
     *
     * @param  int  $month
     * @param  int  $year
     * @param  int  $weekNumber
     * @param  \Carbon\Carbon|string  $kasbonDate
     * @return int
     */
    public function getMaxKasbonUpToDate(int $month, int $year, int $weekNumber, $kasbonDate): int
    {
        $daysWorked = $this->getAttendanceUpToDate($month, $year, $weekNumber, $kasbonDate);
        $dailyWage = $this->daily_wage ?? $this->base_salary;
        return $dailyWage * $daysWorked;
    }

    /**
     * Determine if the given amount can be taken as kasbon.
     *
     * @param  int  $amount
     * @param  int  $month
     * @param  int  $year
     * @param  int  $weekNumber
     * @param  \Carbon\Carbon|string  $kasbonDate
     * @return bool
     */
    public function canTakeKasbon(int $amount, int $month, int $year, int $weekNumber, $kasbonDate): bool
    {
        $maxKasbon = $this->getMaxKasbonUpToDate($month, $year, $weekNumber, $kasbonDate);
        return $amount <= $maxKasbon;
    }
}
