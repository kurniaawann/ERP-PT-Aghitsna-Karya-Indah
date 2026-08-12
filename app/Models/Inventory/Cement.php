<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model untuk entitas Data Semen.
 *
 * Sub modul dari Inventory yang berdiri sendiri (tidak memiliki relasi
 * ke modul lain). Menyimpan data pembelian semen: nomor, tanggal,
 * nama proyek, jumlah sak, harga per sak, dan tanggal lunas.
 * Primary key bersifat string dan di-generate otomatis dengan format SMN-XXXX.
 *
 * @property string       $no
 * @property \Carbon\Carbon|null $tanggal
 * @property string       $nama_proyek
 * @property int          $jumlah
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
        'tanggal',
        'nama_proyek',
        'jumlah',
        'harga',
        'tanggal_lunas',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
        'harga' => 'integer',
        'tanggal_lunas' => 'date',
    ];

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
              ->orWhere('tanggal', 'like', "%{$search}%");
        }));
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

    /**
     * Total nilai harga per sak dikali jumlah sak.
     *
     * @return int
     */
    public function getTotalAttribute(): int
    {
        return $this->jumlah * $this->harga;
    }
}
