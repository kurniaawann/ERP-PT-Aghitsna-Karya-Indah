<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RABSubCategory extends Model
{
    use HasFactory;

    protected $table = 'rab_subcategories';

    protected $fillable = [
        'rab_category_id',
        'number_order',
        'subcategory_name',
        'volume',
        'unit',
        'unit_price',
        'sub_harga',
        'order',
    ];

    protected $casts = [
        'number_order' => 'integer',
        'volume' => 'integer',
        'unit_price' => 'integer',
        'sub_harga' => 'integer',
        'order' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(RABCategory::class, 'rab_category_id');
    }

    public function items()
    {
        return $this->hasMany(RABItem::class, 'rab_subcategory_id')
            ->orderBy('letter_order');
    }
}
