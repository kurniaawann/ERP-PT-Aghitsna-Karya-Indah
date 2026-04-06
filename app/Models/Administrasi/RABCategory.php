<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RABCategory extends Model
{
    use HasFactory;

    protected $table = 'rab_categories';

    protected $fillable = [
        'rab_number',
        'roman_order',
        'category_name',
        'category_subtotal',
        'order',
    ];

    protected $casts = [
        'roman_order' => 'integer',
        'category_subtotal' => 'integer',
        'order' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function rab()
    {
        return $this->belongsTo(RAB::class, 'rab_number', 'rab_number');
    }

    public function subcategories()
    {
        return $this->hasMany(RABSubCategory::class, 'rab_category_id')
            ->orderBy('number_order');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getRomanNumeral()
    {
        $romanNumerals = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            13 => 'XIII',
            14 => 'XIV',
            15 => 'XV',
            16 => 'XVI',
            17 => 'XVII',
            18 => 'XVIII',
            19 => 'XIX',
            20 => 'XX',
        ];
        return $romanNumerals[$this->roman_order] ?? $this->roman_order;
    }
}
