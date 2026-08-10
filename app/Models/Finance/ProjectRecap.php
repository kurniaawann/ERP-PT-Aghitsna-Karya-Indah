<?php

namespace App\Models\Finance;

use App\Models\Report\ProjectFinancialReport;
use App\Models\User;
use App\Services\Finance\RecapProyekService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'location',
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
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * RAB sumber yang menautkan rekap proyek ini.
     */
    public function rab(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Administrasi\RAB::class, 'rab_number', 'rab_number');
    }

    /**
     * Bukti pembayaran yang menautkan ke rekap proyek ini.
     *
     * Bukti disimpan di tabel payment_proofs dengan invoice_type 'recap'
     * dan invoice_number berisi ID rekap (format RP-00001), sehingga
     * mekanisme upload bukti konsisten dengan invoice proyek.
     */
    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class, 'invoice_number', 'id')
            ->where('invoice_type', 'recap')
            ->orderByDesc('created_at');
    }

    /**
     * Laporan Keuangan Proyek yang menautkan ke rekap proyek ini (relasi 1:1).
     *
     * Laporan dibuat otomatis saat dibuka pertama kali dari tombol
     * "Laporan Keuangan" di tabel Rekap Proyek.
     */
    public function financialReport(): HasOne
    {
        return $this->hasOne(ProjectFinancialReport::class, 'project_recap_id', 'id');
    }

    /**
     * Apakah rekap proyek memiliki file design.
     */
    public function hasDesignFile(): bool
    {
        return ! empty($this->design_file);
    }

    // ─── Perhitungan Finansial ─────────────────────────────────────────────

    /**
     * Total nilai rekap (Total RAB).
     */
    public function getTotalAmount(): int
    {
        return (int) ($this->total_rab ?? 0);
    }

    /**
     * Uang masuk (DP) yang diambil dari RAB sumber yang ditautkan.
     */
    public function getDpAmount(): int
    {
        return (int) ($this->rab?->incoming_payment ?? 0);
    }

    /**
     * Rekap proyek tidak memiliki diskon.
     */
    public function getDiscountAmount(): int
    {
        return 0;
    }

    /**
     * Rekap proyek tidak dikenakan PPN.
     */
    public function getPpnAmount(): int
    {
        return 0;
    }

    /**
     * Total pembayaran yang sudah masuk dari bukti pembayaran.
     *
     * @return int Total nominal yang sudah dibayar
     */
    public function getTotalPaidAmount(): int
    {
        $paymentProofs = $this->relationLoaded('paymentProofs')
            ? $this->paymentProofs
            : $this->paymentProofs()->get();

        return (int) max(0, $paymentProofs->sum(fn ($proof) => (int) ($proof->amount ?? 0)));
    }

    /**
     * Sisa pembayaran: Total RAB - DP - total terbayar.
     */
    public function getRemainingAmount(): int
    {
        return (int) max(0, $this->getTotalAmount() - $this->getDpAmount() - $this->getTotalPaidAmount());
    }

    /**
     * Apakah rekap proyek sudah lunas.
     */
    public function isFullyPaid(): bool
    {
        return $this->getRemainingAmount() <= 0;
    }

    /**
     * Progress pembayaran dalam persentase: (DP + terbayar) / Total RAB.
     *
     * @return int Persentase 0-100
     */
    public function getProgressPercent(): int
    {
        $total = $this->getTotalAmount();

        if ($total <= 0) {
            return 0;
        }

        return min(100, (int) round((($this->getDpAmount() + $this->getTotalPaidAmount()) / $total) * 100));
    }
}
