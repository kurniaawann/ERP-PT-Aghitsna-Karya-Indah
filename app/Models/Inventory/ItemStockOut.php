<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Report\SalesRecap;

/**
 * Model ItemStockOut — merepresentasikan data barang keluar.
 *
 * Setiap record menunjukkan bahwa sejumlah item telah dikeluarkan dari gudang,
 * biasanya terkait dengan rekap penjualan (sales recap).
 *
 * @property string $id_stock_out
 * @property string $id_item
 * @property int    $quantity
 * @property string $id_sales_recap
 * @property string|null $project_name
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Inventory\Items|null $item
 * @property-read \App\Models\Report\SalesRecap|null $salesRecap
 * @property-read \Illuminate\Database\Eloquent\Collection $returns
 * @property-read int $total_returned
 * @property-read int $remaining_quantity
 */
class ItemStockOut extends Model
{
    use HasFactory;

    /** @var string Nama tabel database */
    protected $table = 'item_stock_outs';

    /** @var string Primary key model */
    protected $primaryKey = 'id_stock_out';

    /** @var bool Primary key bukan auto-increment (UUID/string) */
    public $incrementing = false;

    /** @var string Tipe primary key */
    protected $keyType = 'string';

    /** @var array<string> Kolom yang boleh di-mass-assign */
    protected $fillable = [
        'id_stock_out',
        'id_item',
        'quantity',
        'id_sales_recap',
        'date',
        'project_name',
    ];

    /** @var array<string, string> Kolom yang di-cast ke tipe tertentu */
    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────

    /**
     * Barang terkait dengan barang keluar ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(Items::class, 'id_item', 'id_item');
    }

    /**
     * Rekap penjualan terkait dengan barang keluar ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesRecap()
    {
        return $this->belongsTo(SalesRecap::class, 'id_sales_recap', 'id_sales_recap');
    }

    /**
     * Daftar pengembalian barang yang terkait dengan barang keluar ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function returns()
    {
        return $this->hasMany(ItemReturn::class, 'id_stock_out', 'id_stock_out');
    }

    // ─── Accessors ────────────────────────────────────────────────

    /**
     * Total jumlah barang yang sudah dikembalikan untuk record ini.
     *
     * @return int
     */
    public function getTotalReturnedAttribute()
    {
        return $this->returns->sum('quantity');
    }

    /**
     * Sisa stok barang saat ini untuk item terkait.
     *
     * Mengambil nilai quantity dari master barang (Items),
     * sehingga sesuai dengan data stok terkini di gudang.
     *
     * @return int
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->item ? (int) $this->item->quantity : 0;
    }

    // ─── Scopes ───────────────────────────────────────────────────

    /**
     * Scope: pencarian berdasarkan ID, ID barang, atau nama barang.
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

        return $query->where('id_stock_out', 'like', "%{$search}%")
            ->orWhere('id_item', 'like', "%{$search}%")
            ->orWhereHas('item', function (Builder $q) use ($search) {
                $q->where('name_item', 'like', "%{$search}%");
            });
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
