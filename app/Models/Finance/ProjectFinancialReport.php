<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Services\Finance\ProjectFinancialReportService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
