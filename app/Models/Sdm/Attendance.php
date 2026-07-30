<?php

namespace App\Models\Sdm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel attendances.
 *
 * Mewakili data absensi karyawan termasuk absensi harian,
 * lembur, dan cuti. Setiap data unik per karyawan per tanggal
 * (dijalankan oleh unique constraint pada employee_id + attendance_date).
 *
 * @property int    $id
 * @property string $employee_id      FK ke employees.employee_code
 * @property string $attendance_date  Tanggal absensi
 * @property string $status           Status: hadir|izin|sakit|cuti|lembur
 * @property float|null $overtime_hours  Jam lembur (untuk status lembur)
 * @property int|null   $overtime_rate   Tarif per jam (untuk status lembur)
 * @property int|null   $overtime_total  Total pembayaran lembur (jam x tarif)
 * @property string|null $notes        Catatan opsional
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Sdm\Employee $employee
 */
class Attendance extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
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
        'created_by',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data native.
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
     * Mendapatkan karyawan yang memiliki data absensi ini.
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mengecek apakah data absensi ini memiliki lembur.
     *
     * @return bool
     */
    public function hasOvertime(): bool
    {
        return $this->status === 'lembur' && $this->overtime_hours > 0;
    }

    /**
     * Mengecek apakah data absensi ini memerlukan potongan (izin, sakit, atau cuti).
     *
     * @return bool
     */
    public function needsDeduction(): bool
    {
        return in_array($this->status, ['izin', 'sakit', 'cuti']);
    }

    /**
     * Scope: memfilter data absensi berdasarkan status.
     *
     * @param  Builder  $query   Query builder Eloquent
     * @param  string   $status  Nilai status untuk difilter
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: memfilter data absensi berdasarkan kode karyawan.
     *
     * @param  Builder  $query         Query builder Eloquent
     * @param  string   $employeeCode  Kode karyawan untuk difilter
     * @return Builder
     */
    public function scopeForEmployee(Builder $query, string $employeeCode): Builder
    {
        return $query->where('employee_id', $employeeCode);
    }

    /**
     * Scope: memfilter data absensi berdasarkan rentang tanggal (inklusif).
     *
     * @param  Builder  $query      Query builder Eloquent
     * @param  string   $startDate  Tanggal mulai (Y-m-d)
     * @param  string   $endDate    Tanggal akhir (Y-m-d)
     * @return Builder
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }
}
