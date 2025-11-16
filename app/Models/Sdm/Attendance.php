<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'status',
        'overtime_hours',
        'overtime_rate',
        'overtime_total',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'integer',
        'overtime_total' => 'integer',
    ];

    /**
     * Relasi ke Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check apakah ada lembur
     */
    public function hasOvertime()
    {
        return $this->status === 'overtime' && $this->overtime_hours > 0;
    }

    /**
     * Check apakah perlu potongan (permission/sick/leave)
     */
    public function needsDeduction()
    {
        return in_array($this->status, ['permission', 'sick', 'leave']);
    }
}
