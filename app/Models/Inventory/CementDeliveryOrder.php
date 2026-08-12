<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk entitas DO Semen (Delivery Order Semen) - HEADER.
 *
 * Sub modul dari Inventory. Merupakan header dari relasi master-detail:
 * satu DO dapat memiliki banyak baris Data Semen (cements).
 * Menyimpan nomor, tanggal DO, tanggal datang, tanggal bayar, dan harga modal.
 * Harga modal diinput manual per DO dan dipakai untuk menghitung profit
 * pada tiap baris Data Semen yang menjadi detailnya.
 * Primary key bersifat string dan di-generate otomatis dengan format DOS-XXXX.
 *
 * @property string       $no
 * @property \Carbon\Carbon|null $tanggal          Tanggal DO
 * @property \Carbon\Carbon|null $tanggal_datang
 * @property \Carbon\Carbon|null $tanggal_bayar
 * @property int          $harga_modal
 */
class CementDeliveryOrder extends Model
{
    use HasFactory;

    /**
     * Primary key model bersifat string dan bukan auto-increment.
     */
    protected $primaryKey = 'no';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang boleh diisi secara massal.
     */
    protected $fillable = [
        'no',
        'tanggal',
        'tanggal_datang',
        'tanggal_bayar',
        'harga_modal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_datang' => 'date',
        'tanggal_bayar' => 'date',
        'harga_modal' => 'integer',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────

    /**
     * Baris Data Semen (detail) yang termasuk dalam DO ini.
     *
     * @return HasMany<Cement, $this>
     */
    public function cements(): HasMany
    {
        return $this->hasMany(Cement::class, 'do_no', 'no');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    /**
     * Scope pencarian berdasarkan nomor atau tanggal DO.
     *
     * @param  Builder       $query
     * @param  string|null   $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn ($q, $search) => $q->where(function ($q) use ($search) {
            $q->where('no', 'like', "%{$search}%")
              ->orWhere('tanggal', 'like', "%{$search}%");
        }));
    }

    /**
     * Scope filter berdasarkan bulan pada tanggal DO.
     *
     * @param  Builder  $query
     * @param  mixed    $month
     * @return Builder
     */
    public function scopeFilterMonth(Builder $query, $month): Builder
    {
        return $query->when($month, fn ($q, $month) => $q->whereMonth('tanggal', $month));
    }

    /**
     * Scope filter berdasarkan tahun pada tanggal DO.
     *
     * @param  Builder  $query
     * @param  mixed    $year
     * @return Builder
     */
    public function scopeFilterYear(Builder $query, $year): Builder
    {
        return $query->when($year, fn ($q, $year) => $q->whereYear('tanggal', $year));
    }

    // ─── Accessors ────────────────────────────────────────────────────

    /**
     * Jumlah baris Data Semen dalam DO ini.
     *
     * @return int
     */
    public function getJumlahBarisAttribute(): int
    {
        return $this->cements->count();
    }

    /**
     * Total keseluruhan zak (volume) dari seluruh baris Data Semen.
     *
     * @return int
     */
    public function getTotalVolumeAttribute(): int
    {
        return $this->cements->sum('jumlah');
    }

    /**
     * Subtotal nilai penjualan: jumlah seluruh (jumlah zak x harga) baris.
     *
     * @return int
     */
    public function getSubtotalAttribute(): int
    {
        return $this->cements->sum(fn (Cement $c) => $c->total);
    }

    /**
     * Profit DO: subtotal dikurangi harga modal.
     *
     * @return int
     */
    public function getProfitAttribute(): int
    {
        return $this->subtotal - $this->harga_modal;
    }

    /**
     * Nomor urut DO dalam satu bulan (dipakai untuk kolom "No" laporan).
     *
     * Nomor disimpan dengan format DOS-YYYYMM-NNN sehingga urutannya
     * direset setiap bulan (Januari: 1,2,3...; Februari mulai 1 lagi).
     *
     * @return int
     */
    public function getNoUrutanAttribute(): int
    {
        $pos = strrpos((string) $this->no, '-');

        return $pos === false ? 0 : (int) substr((string) $this->no, $pos + 1);
    }

    // ─── Static Methods ───────────────────────────────────────────────

    /**
     * Generate nomor DO Semen berikutnya dengan format DOS-YYYYMM-NNN.
     *
     * Nomor dihitung per bulan berdasarkan tanggal DO, lalu direset pada
     * bulan berikutnya. Contoh: DOS-202601-001, DOS-202601-002, ...
     *
     * @param  string|null  $tanggal  Tanggal DO (opsional; default = now()).
     * @return string
     */
    public static function generateNextNo(?string $tanggal = null): string
    {
        $bulan = $tanggal ? date('Ym', strtotime($tanggal)) : date('Ym');
        $prefix = 'DOS-' . $bulan . '-';

        $last = self::where('no', 'like', $prefix . '%')
            ->orderBy('no', 'desc')
            ->value('no');

        $seq = $last ? ((int) substr($last, strrpos($last, '-') + 1)) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
