<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model untuk entitas DO Semen (Delivery Order Semen).
 *
 * Sub modul dari Inventory yang berdiri sendiri (tidak memiliki relasi
 * ke modul lain). Menyimpan data delivery order semen: nomor, tanggal,
 * proyek, volume, satuan, harga, tanggal lunas, dan harga modal.
 * Jumlah (volume x harga) dan profit (jumlah - harga modal) dihitung otomatis.
 * Primary key bersifat string dan di-generate otomatis dengan format DOS-XXXX.
 *
 * @property string       $no
 * @property \Carbon\Carbon|null $tanggal
 * @property string       $proyek
 * @property int          $volume
 * @property string|null  $satuan
 * @property int          $harga
 * @property \Carbon\Carbon|null $tanggal_lunas
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
        'proyek',
        'volume',
        'satuan',
        'harga',
        'tanggal_lunas',
        'harga_modal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'volume' => 'integer',
        'harga' => 'integer',
        'tanggal_lunas' => 'date',
        'harga_modal' => 'integer',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────

    /**
     * Scope pencarian berdasarkan nomor, proyek, atau tanggal.
     *
     * @param  Builder       $query
     * @param  string|null   $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn ($q, $search) => $q->where(function ($q) use ($search) {
            $q->where('no', 'like', "%{$search}%")
              ->orWhere('proyek', 'like', "%{$search}%")
              ->orWhere('tanggal', 'like', "%{$search}%");
        }));
    }

    // ─── Accessors ────────────────────────────────────────────────────

    /**
     * Total nilai: volume dikali harga.
     *
     * @return int
     */
    public function getJumlahAttribute(): int
    {
        return $this->volume * $this->harga;
    }

    /**
     * Profit: jumlah dikurangi harga modal.
     *
     * @return int
     */
    public function getProfitAttribute(): int
    {
        return $this->jumlah - $this->harga_modal;
    }

    // ─── Static Methods ───────────────────────────────────────────────

    /**
     * Generate nomor DO Semen berikutnya dengan format DOS-XXXX.
     *
     * @return string
     */
    public static function generateNextNo(): string
    {
        $lastNo = self::max('no');

        if (!$lastNo) {
            return 'DOS-0001';
        }

        $lastNumber = (int) substr($lastNo, 4);

        return 'DOS-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
