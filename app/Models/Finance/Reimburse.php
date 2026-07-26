<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel reimburses.
 *
 * Menggunakan reimburse_code sebagai primary key (bukan auto-increment ID).
 * Format kode: RMB001, RMB002, RMB003, dst.
 *
 * @property string                    $reimburse_code
 * @property \Carbon\Carbon            $date
 * @property string                    $project_name
 * @property string                    $expense_description
 * @property int                       $total_amount
 * @property \Carbon\Carbon            $due_date
 * @property string                    $status           draft|approved|rejected
 * @property string|null               $notes
 * @property \Carbon\Carbon|null       $status_changed_at
 */
class Reimburse extends Model
{
    use HasFactory;

    /**
     * Primary key adalah reimburse_code (bukan auto-increment ID).
     */
    protected $primaryKey = 'reimburse_code';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Field yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reimburse_code',
        'date',
        'project_name',
        'expense_description',
        'total_amount',
        'due_date',
        'status',
        'notes',
        'status_changed_at',
        'created_by',
    ];

    /**
     * Type casting untuk atribut model.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'integer',
        'status_changed_at' => 'datetime',
    ];

    /**
     * Generate kode reimburse berikutnya.
     *
     * Format: RMB001, RMB002, RMB003, dst.
     * Mengambil kode terakhir berdasarkan sort descending.
     *
     * @return string  Kode reimburse berikutnya
     */
    public static function generateReimburseCode(): string
    {
        $lastReimburse = self::orderBy('reimburse_code', 'desc')->first();

        if (!$lastReimburse) {
            return 'RMB001';
        }

        $lastNumber = (int) substr($lastReimburse->reimburse_code, 3);
        $newNumber = $lastNumber + 1;

        return 'RMB' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor untuk format tanggal Indonesia.
     *
     * @return string  Format: d/m/Y
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date ? $this->date->format('d/m/Y') : '-';
    }

    /**
     * Accessor untuk format due date Indonesia.
     *
     * @return string  Format: d/m/Y
     */
    public function getFormattedDueDateAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('d/m/Y') : '-';
    }

    /**
     * Accessor untuk format total amount dengan Rupiah.
     *
     * @return string  Format: Rp 1.000.000
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    /**
     * Accessor untuk label status dalam Bahasa Indonesia.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'Draft',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Accessor untuk CSS class badge status.
     *
     * @return string  Tailwind CSS classes
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            'draft' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Accessor untuk format tanggal perubahan status.
     *
     * @return string  Format: d/m/Y H:i
     */
    public function getFormattedStatusChangedAtAttribute(): string
    {
        return $this->status_changed_at
            ? $this->status_changed_at->format('d/m/Y H:i')
            : '-';
    }
}
