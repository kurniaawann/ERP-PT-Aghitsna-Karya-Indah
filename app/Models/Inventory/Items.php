<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_item';
    public $incrementing = false; // ID manual
    protected $keyType = 'string';

    protected $fillable = [
        'id_item',
        'name_item',
        'quantity',
        'capital_price',
        'selling_price',
    ];

    // Relationships
    public function stockIns()
    {
        return $this->hasMany(ItemStockIn::class, 'id_item', 'id_item');
    }

    public static function generateNextId(): string
    {
        $lastId = self::max('id_item');

        if (!$lastId) {
            return 'ITM-0001';
        }

        $lastNumber = (int) substr($lastId, 4);

        return 'ITM-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function stockOuts()
    {
        return $this->hasMany(ItemStockOut::class, 'id_item', 'id_item');
    }

    public function returns()
    {
        return $this->hasMany(ItemReturn::class, 'id_item', 'id_item');
    }
}
