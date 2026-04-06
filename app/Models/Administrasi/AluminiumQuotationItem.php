<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AluminiumQuotationItem extends Model
{
    use HasFactory;

    protected $table = 'aluminium_quotation_items';

    protected $fillable = [
        'group_id',
        'order_number',
        'description',
        'volume',
        'unit',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'total_price' => 'integer',
        'order_number' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function group()
    {
        return $this->belongsTo(AluminiumQuotationGroup::class, 'group_id');
    }
}
