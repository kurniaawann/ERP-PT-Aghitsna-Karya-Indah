<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model ItemReturn — merepresentasikan data pengembalian barang.
 *
 * Setiap record menunjukkan bahwa sejumlah barang telah dikembalikan,
 * baik dari supplier (return masuk) maupun dari proyek/konsumen (return keluar).
 *
 * @property string $id_return
 * @property string $id_item
 * @property string|null $id_stock_out
 * @property string|null $id_stock_in
 * @property int    $quantity
 * @property string|null $return_type
 * @property string|null $reason
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Inventory\Items|null $item
 * @property-read \App\Models\Inventory\ItemStockOut|null $stockOut
 * @property-read \App\Models\Inventory\ItemStockIn|null $stockIn
 */
class ItemReturn extends Model
{
    use HasFactory;

    /** @var string Nama tabel database */
    protected $table = 'item_returns';

    /** @var string Primary key model */
    protected $primaryKey = 'id_return';

    /** @var bool Primary key bukan auto-increment (UUID/string) */
    public $incrementing = false;

    /** @var string Tipe primary key */
    protected $keyType = 'string';

    /** @var array<string> Kolom yang boleh di-mass-assign */
    protected $fillable = [
        'id_return',
        'id_item',
        'id_stock_out',
        'id_stock_in',
        'quantity',
        'reason',
        'notes',
        'return_type',
        'date',
    ];

    /** @var array<string, string> Kolom yang di-cast ke tipe tertentu */
    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────

    /**
     * Barang terkait dengan pengembalian ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(Items::class, 'id_item', 'id_item');
    }

    /**
     * Barang keluar terkait dengan pengembalian ini (tipe keluar).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stockOut()
    {
        return $this->belongsTo(ItemStockOut::class, 'id_stock_out', 'id_stock_out');
    }

    /**
     * Barang masuk terkait dengan pengembalian ini (tipe masuk).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stockIn()
    {
        return $this->belongsTo(ItemStockIn::class, 'id_stock_in', 'id_stock_in');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    /**
     * Scope: pencarian berdasarkan ID return, ID barang, atau nama barang.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where('id_return', 'like', "%{$search}%")
            ->orWhere('id_item', 'like', "%{$search}%")
            ->orWhereHas('item', function (Builder $q) use ($search) {
                $q->where('name_item', 'like', "%{$search}%");
            });
    }

    /**
     * Scope: filter berdasarkan tipe return (masuk/keluar).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $returnType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterReturnType(Builder $query, ?string $returnType): Builder
    {
        if (!$returnType) {
            return $query;
        }

        return $query->where('return_type', $returnType);
    }

    /**
     * Scope: filter berdasarkan bulan dari kolom date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|int|null $month
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterMonth(Builder $query, $month): Builder
    {
        if (!$month) {
            return $query;
        }

        return $query->whereMonth('date', $month);
    }

    /**
     * Scope: filter berdasarkan tahun dari kolom date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|int|null $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterYear(Builder $query, $year): Builder
    {
        if (!$year) {
            return $query;
        }

        return $query->whereYear('date', $year);
    }
}
