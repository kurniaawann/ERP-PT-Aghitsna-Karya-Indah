<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reimburse extends Model
{
    use HasFactory;

    /**
     * Primary key adalah reimburse_code (bukan auto-increment ID)
     * Format: RMB001, RMB002, RMB003, dst
     */
    protected $primaryKey = 'reimburse_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reimburse_code',
        'date',
        'project_name',
        'expense_description',
        'total_amount',
        'due_date',
        'status',
        'notes',
        'submitted_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Relasi ke User yang mengajukan reimburse (admin)
     */
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Relasi ke User yang menyetujui/menolak (super admin)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate kode reimburse berikutnya
     * Format: RMB001, RMB002, RMB003, dst
     * 
     * @return string
     */
    public static function generateReimburseCode()
    {
        // Ambil reimburse terakhir berdasarkan kode (descending)
        $lastReimburse = self::orderBy('reimburse_code', 'desc')->first();

        // Jika belum ada data, mulai dari RMB001
        if (!$lastReimburse) {
            return 'RMB001';
        }

        // Ambil nomor dari kode terakhir (contoh: RMB001 -> 001)
        $lastNumber = (int) substr($lastReimburse->reimburse_code, 3);

        // Increment nomor
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        // str_pad akan menambahkan 0 di depan jika kurang dari 3 digit
        return 'RMB' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Accessor untuk format tanggal Indonesia
     */
    public function getFormattedDateAttribute()
    {
        /** @var \Carbon\Carbon|null $date */
        $date = $this->date;
        return $date ? $date->format('d/m/Y') : '-';
    }

    /**
     * Accessor untuk format due date Indonesia
     */
    public function getFormattedDueDateAttribute()
    {
        /** @var \Carbon\Carbon|null $dueDate */
        $dueDate = $this->due_date;
        return $dueDate ? $dueDate->format('d/m/Y') : '-';
    }

    /**
     * Accessor untuk format total amount dengan Rupiah
     */
    public function getFormattedTotalAmountAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    /**
     * Accessor untuk status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Accessor untuk status badge class (untuk styling)
     */
    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'draft' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
