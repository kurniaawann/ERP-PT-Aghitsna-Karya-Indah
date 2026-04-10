<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemReturn extends Model
{
    use HasFactory;

    protected $table = 'item_returns';
    protected $primaryKey = 'id_return';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_return',
        'id_item',
        'id_stock_out',
        'id_stock_in',
        'quantity',
        'alasan',
        'keterangan',
        'return_type',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'quantity' => 'integer',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(Items::class, 'id_item', 'id_item');
    }

    public function stockOut()
    {
        return $this->belongsTo(ItemStockOut::class, 'id_stock_out', 'id_stock_out');
    }

    public function stockIn()
    {
        return $this->belongsTo(ItemStockIn::class, 'id_stock_in', 'id_stock_in');
    }
}
