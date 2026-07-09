<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Report\SalesRecap;

class ItemStockOut extends Model
{
    use HasFactory;

    protected $table = 'item_stock_outs';
    protected $primaryKey = 'id_stock_out';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_stock_out',
        'id_item',
        'quantity',
        'id_sales_recap',
        'date',
        'project_name',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(Items::class, 'id_item', 'id_item');
    }

    public function salesRecap()
    {
        return $this->belongsTo(SalesRecap::class, 'id_sales_recap', 'id_sales_recap');
    }

    public function returns()
    {
        return $this->hasMany(ItemReturn::class, 'id_stock_out', 'id_stock_out');
    }

    // Get total quantity returned
    public function getTotalReturnedAttribute()
    {
        return $this->returns->sum('quantity');
    }

    // Get remaining quantity (after returns)
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->total_returned;
    }
}
