<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RABMiscellaneousCost extends Model
{
    use HasFactory;

    protected $table = 'rab_miscellaneous_costs';

    protected $fillable = [
        'rab_number',
        'item_order',
        'item_name',
        'amount',
        'order',
    ];

    protected $casts = [
        'amount' => 'integer',
        'item_order' => 'integer',
        'order' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function rab()
    {
        return $this->belongsTo(RAB::class, 'rab_number', 'rab_number');
    }
}
