<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $table = 'purchase_invoices';

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

    protected $casts = [
        'date' => 'date',
        'selling_price' => 'integer',
        'ppn_percentage' => 'integer',
        'ppn_tax' => 'integer',
    ];

    /**
     * Calculate total with PPN
     */
    public function getTotalAttribute()
    {
        return $this->harga_jual + $this->ppn_pengenaan_pajak;
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeFilterByDate($query, $tanggal)
    {
        return $query->whereDate('tanggal', $tanggal);
    }

    /**
     * Scope untuk filter berdasarkan range tanggal
     */
    public function scopeFilterByDateRange($query, $start, $end)
    {
        return $query->whereBetween('tanggal', [$start, $end]);
    }

    /**
     * Scope untuk filter berdasarkan nama material
     */
    public function scopeFilterByMaterial($query, $nama_material)
    {
        return $query->where('nama_material', 'like', "%{$nama_material}%");
    }
}
