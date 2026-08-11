<?php

namespace App\Models\Report;

use App\Models\Finance\ProjectRecap;
use App\Models\User;
use App\Services\Report\ProjectFinancialReportService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Model untuk Laporan Keuangan Proyek.
 *
 * Satu Rekap Proyek (project_recaps) memiliki satu Laporan Keuangan Proyek
 * (relasi 1:1). Laporan dibuat otomatis saat tombol "Laporan Keuangan" pada
 * tabel Rekap Proyek diklik pertama kali.
 *
 * Table: project_financial_reports
 * Primary Key: id (string, format: LFP-00001)
 */
class ProjectFinancialReport extends Model
{
    use HasFactory;

    protected $table = 'project_financial_reports';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_recap_id',
        'created_by',
    ];

    /**
     * Boot method untuk auto-generate ID saat creating.
     *
     * ID di-generate oleh ProjectFinancialReportService::generateId() yang
     * sudah menggunakan lockForUpdate() untuk mencegah race condition.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = app(ProjectFinancialReportService::class)->generateId();
            }
        });

        // Membersihkan file bukti (proof_file) milik item sebelum laporan
        // dihapus. Event deleting menembak sebelum record laporan terhapus,
        // jadi item masih bisa dibaca. Hanya jalur lewat service yang selama ini
        // membersihkan file (RecapProyekService::bulkDelete); hapus langsung
        // via model tidak menghapus file karena cascade DB (foreign key) tidak
        // menembakkan event Eloquent untuk item.
        static::deleting(function ($model) {
            foreach ($model->items as $item) {
                if ($item->proof_file) {
                    Storage::disk('public')->delete($item->proof_file);
                }
            }
        });
    }

    /**
     * Rekap proyek yang dimiliki laporan ini.
     */
    public function recap(): BelongsTo
    {
        return $this->belongsTo(ProjectRecap::class, 'project_recap_id', 'id');
    }

    /**
     * User yang membuat laporan ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Baris "Bon" pada laporan keuangan proyek.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProjectFinancialReportItem::class, 'project_financial_report_id', 'id');
    }
}
