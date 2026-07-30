<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model untuk entitas Barang Masuk (Stock In).
 *
 * Menyimpan catatan penerimaan barang beserta jumlah, harga modal,
 * keterangan, dan tanggal. Primary key bersifat string
 * dengan format SIN-YYYYMMDD-XXXX.
 *
 * @property string $id_stock_in
 * @property string $id_item
 * @property int    $quantity
 * @property int    $capital_price
 * @property string|null $notes
 * @property \Carbon\Carbon $date
 */
class ItemStockIn extends Model
{
    use HasFactory;

    /**
     * Nama tabel database.
     */
    protected $table = 'item_stock_ins';

    /**
     * Primary key model bersifat string dan bukan auto-increment.
     */
    protected $primaryKey = 'id_stock_in';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang boleh diisi secara massal.
     */
    protected $fillable = [
        'id_stock_in',
        'id_item',
        'quantity',
        'capital_price',
        'notes',
        'date',
    ];

    /**
     * Konversi tipe data kolom.
     */
    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'capital_price' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    /**
     * Relasi ke data Barang (Items).
     *
     * @return BelongsTo
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'id_item', 'id_item');
    }

    // ─── Accessors ────────────────────────────────────────────────────

    /**
     * Total harga modal (quantity x capital_price).
     *
     * @return int
     */
    public function getTotalCapitalAttribute(): int
    {
        return $this->quantity * $this->capital_price;
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    /**
     * Scope pencarian berdasarkan ID masuk, ID barang, atau nama barang.
     *
     * @param  Builder       $query
     * @param  string|null   $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn ($q, $search) => $q->where(function ($q) use ($search) {
            $q->where('id_stock_in', 'like', "%{$search}%")
              ->orWhere('id_item', 'like', "%{$search}%")
              ->orWhereHas('item', fn ($sub) => $sub->where('name_item', 'like', "%{$search}%"));
        }));
    }

    /**
     * Scope filter berdasarkan bulan.
     *
     * @param  Builder      $query
     * @param  string|int|null  $month
     * @return Builder
     */
    public function scopeFilterMonth(Builder $query, $month): Builder
    {
        return $query->when($month, fn ($q, $month) => $q->whereMonth('date', $month));
    }

    /**
     * Scope filter berdasarkan tahun.
     *
     * @param  Builder      $query
     * @param  string|int|null  $year
     * @return Builder
     */
    public function scopeFilterYear(Builder $query, $year): Builder
    {
        return $query->when($year, fn ($q, $year) => $q->whereYear('date', $year));
    }
}
