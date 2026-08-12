<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk entitas Data Semen (baris detail dari DO Semen).
 *
 * Sub modul dari Inventory. Merupakan detail dari relasi master-detail:
 * setiap baris Data Semen terikat ke satu DO Semen (cement_delivery_orders)
 * melalui kolom do_no. Menyimpan tanggal, nama proyek, jumlah zak (= volume),
 * satuan, harga per zak, dan tanggal lunas.
 * Primary key bersifat string dan di-generate otomatis dengan format SMN-XXXX.
 *
 * @property string       $no
 * @property string|null  $do_no
 * @property \Carbon\Carbon|null $tanggal
 * @property string       $nama_proyek
 * @property int          $jumlah
 * @property string|null  $satuan
 * @property int          $harga
 * @property \Carbon\Carbon|null $tanggal_lunas
 */
class Cement extends Model
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
        'do_no',
        'tanggal',
        'nama_proyek',
        'jumlah',
        'satuan',
        'harga',
        'tanggal_lunas',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
        'harga' => 'integer',
        'tanggal_lunas' => 'date',
    ];

    // ─── Relasi ───────────────────────────────────────────────────────

    /**
     * DO Semen (header) yang menaungi baris Data Semen ini.
     *
     * @return BelongsTo<CementDeliveryOrder, $this>
     */
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(CementDeliveryOrder::class, 'do_no', 'no');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    /**
     * Scope pencarian berdasarkan nomor, nama proyek, atau tanggal.
     *
     * @param  Builder       $query
     * @param  string|null   $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn ($q, $search) => $q->where(function ($q) use ($search) {
            $q->where('no', 'like', "%{$search}%")
              ->orWhere('nama_proyek', 'like', "%{$search}%")
              ->orWhere('do_no', 'like', "%{$search}%")
              ->orWhere('tanggal', 'like', "%{$search}%");
        }));
    }

    // ─── Accessors ────────────────────────────────────────────────────

    /**
     * Total nilai baris: jumlah zak dikali harga per zak.
     *
     * @return int
     */
    public function getTotalAttribute(): int
    {
        return $this->jumlah * $this->harga;
    }

    /**
     * Harga modal diambil dari DO (header) yang menaungi baris ini.
     *
     * @return int
     */
    public function getHargaModalAttribute(): int
    {
        return $this->deliveryOrder->harga_modal ?? 0;
    }

    /**
     * Profit baris: total dikurangi harga modal DO.
     *
     * @return int
     */
    public function getProfitAttribute(): int
    {
        return $this->total - $this->harga_modal;
    }

    // ─── Static Methods ───────────────────────────────────────────────

    /**
     * Generate nomor data semen berikutnya dengan format SMN-XXXX.
     *
     * @return string
     */
    public static function generateNextNo(): string
    {
        $lastNo = self::max('no');

        if (!$lastNo) {
            return 'SMN-0001';
        }

        $lastNumber = (int) substr($lastNo, 4);

        return 'SMN-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
