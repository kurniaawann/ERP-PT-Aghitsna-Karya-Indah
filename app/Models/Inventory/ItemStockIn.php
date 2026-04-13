<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemStockIn extends Model
{
    use HasFactory;

    protected $table = 'item_stock_ins';
    protected $primaryKey = 'id_stock_in';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_stock_in',
        'id_item',
        'quantity',
        'capital_price',
        'keterangan',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'quantity' => 'integer',
        'capital_price' => 'integer',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(Items::class, 'id_item', 'id_item');
    }

    // Calculate total harga modal
    public function getTotalCapitalAttribute()
    {
        return $this->quantity * $this->capital_price;
    }
}
