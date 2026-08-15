<?php

namespace App\Models\Finance;

use App\Services\Finance\SemenInvoiceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Model untuk Invoice Semen.
 *
 * Satu invoice semén menampung banyak proyek (kolom `projects` JSON).
 * Setiap proyek memiliki nama proyek, rekening pembayaran, dan banyak
 * baris data semen (items) dengan nomor urut yang di-reset per proyek
 * (mulai dari 1 kembali untuk proyek berikutnya).
 */
class InvoiceSemen extends Model
{
    use HasFactory;

    protected $table = 'semen_invoices';
    protected $primaryKey = 'invoice_number';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'projects',
        'total_amount',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'projects' => 'json',
        'total_amount' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'invoice_number';
    }

    /**
     * Relasi ke user yang membuat invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mengakses daftar proyek sebagai collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getProjectsCollectionAttribute(): \Illuminate\Support\Collection
    {
        $projects = is_string($this->projects) ? json_decode($this->projects, true) : $this->projects;

        return collect(is_array($projects) ? $projects : []);
    }

    /**
     * Total nilai invoice (sudah disimpan di kolom total_amount).
     */
    public function getNetAmount(): int
    {
        return (int) $this->total_amount;
    }

    /**
     * Jumlah proyek yang ada di dalam invoice ini.
     *
     * @return int
     */
    public function getProyekCountAttribute(): int
    {
        return $this->getProjectsCollectionAttribute()->count();
    }

    /**
     * Daftar nama proyek yang digabung dengan koma.
     *
     * @return string
     */
    public function getNamaProyekListAttribute(): string
    {
        return $this->getProjectsCollectionAttribute()
            ->pluck('nama_proyek')
            ->filter()
            ->implode(', ');
    }
}