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
        'ppn_percentage' => 'decimal:2',
        'ppn_tax' => 'integer',
    ];

    /**
     * Calculate total with PPN
     */
    public function getTotalAttribute()
    {
        return $this->selling_price + $this->ppn_tax;
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeFilterByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope untuk filter berdasarkan range tanggal
     */
    public function scopeFilterByDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Scope untuk filter berdasarkan nama material
     */
    public function scopeFilterByMaterial($query, $material_name)
    {
        return $query->where('material_name', 'like', "%{$material_name}%");
    }
}
