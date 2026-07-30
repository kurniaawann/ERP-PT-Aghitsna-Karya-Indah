<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model untuk Faktur Pembelian.
 *
 * Menyimpan data faktur pembelian material/barang beserta informasi
 * pajak (PPN) yang terkait.
 */
class PurchaseInvoice extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan.
     *
     * @var string
     */
    protected $table = 'purchase_invoices';

    /**
     * Kolom yang boleh diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'material_name',
        'npwp',
        'tax_number_code',
        'item_name',
        'selling_price',
        'ppn_percentage',
        'ppn_tax',
        'notes',
    ];

    /**
     * Konversi tipe data kolom.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'selling_price' => 'integer',
        'ppn_percentage' => 'decimal:2',
        'ppn_tax' => 'integer',
    ];

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Total harga: harga jual + PPN.
     *
     * @return int
     */
    public function getTotalAttribute(): int
    {
        return $this->selling_price + $this->ppn_tax;
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope: filter berdasarkan tanggal spesifik.
     *
     * @param  Builder $query
     * @param  string  $date  Format tanggal yang bisa diterima Carbon
     * @return Builder
     */
    public function scopeFilterByDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope: filter berdasarkan range tanggal.
     *
     * @param  Builder $query
     * @param  string  $start
     * @param  string  $end
     * @return Builder
     */
    public function scopeFilterByDateRange(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Scope: filter berdasarkan nama material.
     *
     * @param  Builder $query
     * @param  string  $materialName
     * @return Builder
     */
    public function scopeFilterByMaterial(Builder $query, string $materialName): Builder
    {
        return $query->where('material_name', 'like', "%{$materialName}%");
    }

    /**
     * Scope: filter berdasarkan kata kunci pencarian (material, barang, NPWP).
     *
     * @param  Builder     $query
     * @param  string|null $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('material_name', 'like', "%{$search}%")
                ->orWhere('item_name', 'like', "%{$search}%")
                ->orWhere('npwp', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: filter berdasarkan bulan.
     *
     * @param  Builder     $query
     * @param  string|null $month  Bulan (1-12)
     * @return Builder
     */
    public function scopeFilterByMonth(Builder $query, ?string $month): Builder
    {
        if (!$month) {
            return $query;
        }

        return $query->whereMonth('date', $month);
    }

    /**
     * Scope: filter berdasarkan tahun.
     *
     * @param  Builder     $query
     * @param  string|null $year
     * @return Builder
     */
    public function scopeFilterByYear(Builder $query, ?string $year): Builder
    {
        if (!$year) {
            return $query;
        }

        return $query->whereYear('date', $year);
    }
}
