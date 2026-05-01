<?php

namespace App\Models\Notification;

use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'period_month',
        'period_year',
        'reminder_date',
        'status',
        'notification_sent_at',
        'notes',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'reminder_date' => 'date',
        'notification_sent_at' => 'datetime',
    ];

    /**
     * Relasi ke Payroll
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    /**
     * Relasi ke Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, $month, $year)
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check apakah gaji dalam status draft (belum dibayar)
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Check apakah gaji sudah dibayar
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check apakah notifikasi sudah dikirim
     */
    public function isNotified()
    {
        return $this->notification_sent_at !== null;
    }
}

