<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for the attendances table.
 *
 * Represents employee attendance records including daily attendance,
 * overtime, and leave. Each record is unique per employee per date
 * (enforced by a unique constraint on employee_id + attendance_date).
 *
 * @property int    $id
 * @property string $employee_id      FK to employees.employee_code
 * @property string $attendance_date  Date of attendance
 * @property string $status           Status: hadir|izin|sakit|cuti|lembur
 * @property float|null $overtime_hours  Overtime hours (for lembur status)
 * @property int|null   $overtime_rate   Rate per hour (for lembur status)
 * @property int|null   $overtime_total  Total overtime pay (hours x rate)
 * @property string|null $notes        Optional notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Sdm\Employee $employee
 */
class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'status',
        'overtime_hours',
        'overtime_rate',
        'overtime_total',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attendance_date' => 'date',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'integer',
        'overtime_total' => 'integer',
    ];

    /**
     * Get the employee that owns this attendance record.
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    /**
     * Check if this attendance record has overtime.
     *
     * @return bool
     */
    public function hasOvertime(): bool
    {
        return $this->status === 'lembur' && $this->overtime_hours > 0;
    }

    /**
     * Check if this attendance record requires a deduction (izin, sakit, or cuti).
     *
     * @return bool
     */
    public function needsDeduction(): bool
    {
        return in_array($this->status, ['izin', 'sakit', 'cuti']);
    }

    /**
     * Scope: filter attendance records by status.
     *
     * @param  Builder  $query   Eloquent query builder
     * @param  string   $status  Status value to filter by
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter attendance records by employee code.
     *
     * @param  Builder  $query         Eloquent query builder
     * @param  string   $employeeCode  Employee code to filter by
     * @return Builder
     */
    public function scopeForEmployee(Builder $query, string $employeeCode): Builder
    {
        return $query->where('employee_id', $employeeCode);
    }

    /**
     * Scope: filter attendance records by date range (inclusive).
     *
     * @param  Builder  $query      Eloquent query builder
     * @param  string   $startDate  Start date (Y-m-d)
     * @param  string   $endDate    End date (Y-m-d)
     * @return Builder
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }
}
