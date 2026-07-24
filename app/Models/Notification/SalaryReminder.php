<?php

namespace App\Models\Notification;

use App\Models\Sdm\Employee;
use App\Models\Sdm\Payroll;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk data Salary Reminder (Pengingat Gaji Karyawan).
 *
 * Model ini menyimpan informasi pengingat pembayaran gaji karyawan
 * yang otomatis dibuat ketika payroll digenerate.
 *
 * Status:
 * - draft: Gaji belum dibayar
 * - paid: Gaji sudah dibayar
 *
 * Relasi:
 * - payroll: Payroll terkait (nullable, cascade delete)
 * - employee: Karyawan terkait (nullable, cascade delete)
 */
class SalaryReminder extends Model
{
    use HasFactory;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<string>
     */
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'period_month',
        'period_year',
        'reminder_date',
        'status',
        'notification_sent_at',
        'notes',
        'created_by',
    ];

    /**
     * Konversi tipe data kolom.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'reminder_date' => 'date',
        'notification_sent_at' => 'datetime',
    ];

    /**
     * Relasi ke model Payroll.
     *
     * @return BelongsTo
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    /**
     * Relasi ke model Employee.
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope pencarian berdasarkan nama karyawan atau employee_id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null                             $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where('employee_id', 'like', "%{$search}%")
            ->orWhereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
    }

    /**
     * Scope filter berdasarkan periode (bulan dan tahun).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int                                     $month
     * @param  int                                     $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }

    /**
     * Scope filter berdasarkan status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string                                  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Mengecek apakah gaji dalam status draft (belum dibayar).
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Mengecek apakah gaji sudah dibayar.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Mengecek apakah notifikasi sudah dikirim.
     *
     * @return bool
     */
    public function isNotified(): bool
    {
        return $this->notification_sent_at !== null;
    }
}
