<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Services\Finance\RecapProyekService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Rekap Proyek (standalone).
 *
 * Merepresentasikan rekap proyek yang diinput manual oleh user.
 * Berbeda dari sebelumnya (yang mengambil data dari invoice proyek),
 * modul ini merupakan sub-modul mandiri dengan data:
 * - No (id auto-generate format RP-00001)
 * - Nama Proyek
 * - Total RAB
 * - File design (unggahan)
 *
 * Table: project_recaps
 * Primary Key: id (string, format: RP-00001)
 */
class ProjectRecap extends Model
{
    use HasFactory;

    protected $table = 'project_recaps';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'rab_number',
        'project_name',
        'total_rab',
        'design_file',
        'design_file_name',
        'created_by',
    ];

    protected $casts = [
        'total_rab' => 'integer',
    ];

    /**
     * Boot method untuk auto-generate ID saat creating.
     *
     * ID di-generate oleh RecapProyekService::generateId() yang sudah
     * menggunakan lockForUpdate() untuk mencegah race condition.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = app(RecapProyekService::class)->generateId();
            }
        });
    }

    /**
     * Relasi ke user yang membuat rekap proyek ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * RAB sumber yang menautkan rekap proyek ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rab(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Administrasi\RAB::class, 'rab_number', 'rab_number');
    }

    /**
     * Apakah rekap proyek memiliki file design.
     *
     * @return bool
     */
    public function hasDesignFile(): bool
    {
        return !empty($this->design_file);
    }
}
