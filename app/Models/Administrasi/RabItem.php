<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RABItem extends Model
{
    use HasFactory;

    protected $table = 'rab_items';

    protected $fillable = [
        'rab_subcategory_id',
        'letter_order',
        'item_description',
        'order',
    ];

    protected $casts = [
        'letter_order' => 'integer',
        'order' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function subcategory()
    {
        return $this->belongsTo(RABSubCategory::class, 'rab_subcategory_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getLetter()
    {
        return chr(96 + $this->letter_order); // a, b, c, d, etc
    }
}
