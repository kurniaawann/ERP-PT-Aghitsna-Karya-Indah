<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AluminiumQuotationGroup extends Model
{
    use HasFactory;

    protected $table = 'aluminium_quotation_groups';

    protected $fillable = [
        'quotation_number',
        'order_number',
        'name',
        'subtotal',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'order_number' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function quotation()
    {
        return $this->belongsTo(AluminiumQuotation::class, 'quotation_number', 'quotation_number');
    }

    public function items()
    {
        return $this->hasMany(AluminiumQuotationItem::class, 'group_id')->orderBy('order_number');
    }
}
