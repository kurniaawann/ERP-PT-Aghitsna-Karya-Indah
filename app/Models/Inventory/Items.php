<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk entitas Item (Barang).
 *
 * Menyimpan data master barang meliputi ID, nama, jumlah stok,
 * harga modal, dan harga jual. Primary key bersifat string
 * dan di-generate otomatis dengan format ITM-XXXX.
 *
 * @property string $id_item
 * @property string $name_item
 * @property int    $quantity
 * @property int    $capital_price
 * @property int    $selling_price
 */
class Items extends Model
{
    use HasFactory;

    /**
     * Primary key model bersifat string dan bukan auto-increment.
     */
    protected $primaryKey = 'id_item';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang boleh diisi secara massal.
     */
    protected $fillable = [
        'id_item',
        'name_item',
        'quantity',
        'capital_price',
        'selling_price',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    /**
     * Relasi ke data Barang Masuk (Stock In).
     *
     * @return HasMany
     */
    public function stockIns(): HasMany
    {
        return $this->hasMany(ItemStockIn::class, 'id_item', 'id_item');
    }

    /**
     * Relasi ke data Barang Keluar (Stock Out).
     *
     * @return HasMany
     */
    public function stockOuts(): HasMany
    {
        return $this->hasMany(ItemStockOut::class, 'id_item', 'id_item');
    }

    /**
     * Relasi ke data Pengembalian Barang (Return).
     *
     * @return HasMany
     */
    public function returns(): HasMany
    {
        return $this->hasMany(ItemReturn::class, 'id_item', 'id_item');
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    /**
     * Scope pencarian berdasarkan nama atau ID barang.
     *
     * @param  Builder       $query
     * @param  string|null   $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn ($q, $search) => $q->where(function ($q) use ($search) {
            $q->where('name_item', 'like', "%{$search}%")
              ->orWhere('id_item', 'like', "%{$search}%");
        }));
    }

    // ─── Static Methods ───────────────────────────────────────────────

    /**
     * Generate ID barang berikutnya dengan format ITM-XXXX.
     *
     * @return string
     */
    public static function generateNextId(): string
    {
        $lastId = self::max('id_item');

        if (!$lastId) {
            return 'ITM-0001';
        }

        $lastNumber = (int) substr($lastId, 4);

        return 'ITM-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
