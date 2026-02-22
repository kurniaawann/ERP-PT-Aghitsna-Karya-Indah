<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectQuotationGroup extends Model
{
    use HasFactory;

    protected $table = 'project_quotation_groups';

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
        return $this->belongsTo(ProjectQuotation::class, 'quotation_number', 'quotation_number');
    }

    public function items()
    {
        return $this->hasMany(ProjectQuotationItem::class, 'group_id')->orderBy('order_number');
    }
}
