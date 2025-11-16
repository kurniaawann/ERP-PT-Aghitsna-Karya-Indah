<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'name',
        'position',
        'phone',
        'email',
        'address',
        'base_salary',
        'join_date',
    ];

    protected $casts = [
        'join_date' => 'date',
        'base_salary' => 'integer',
    ];

    /**
     * Relasi ke Attendance
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relasi ke Payroll
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
