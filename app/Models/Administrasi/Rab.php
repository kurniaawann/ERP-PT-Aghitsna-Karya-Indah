<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RAB extends Model
{
    use HasFactory;

    protected $table = 'rabs';
    protected $primaryKey = 'rab_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'rab_number',
        'sequence_number',
        'date',
        'recipient',
        'recipient_address',
        'intro_text',
        'total_amount',
        'amount_in_words',
        'selected_payment_accounts',
        'signed_by',
        'division',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'integer',
        'sequence_number' => 'integer',
        'selected_payment_accounts' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'rab_number';
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function categories()
    {
        return $this->hasMany(RABCategory::class, 'rab_number', 'rab_number')
            ->orderBy('roman_order');
    }

    // ─── Static Methods ────────────────────────────────────────────────────────

    public static function generateRABNumber()
    {
        $year = date('y');
        $sequence = self::getNextSequenceNumber();
        return "{$sequence}/RAB/PT.AKI/{$year}";
    }

    public static function getNextSequenceNumber()
    {
        $latestSequence = self::max('sequence_number') ?? 0;
        return $latestSequence + 1;
    }

    // ─── Instance Methods ──────────────────────────────────────────────────────

    public function getRawRABData()
    {
        return $this->categories()
            ->with(['subcategories.items'])
            ->get()
            ->map(function ($category) {
                return [
                    'category_name' => $category->category_name,
                    'subcategories' => $category->subcategories->map(function ($subcategory) {
                        return [
                            'subcategory_name' => $subcategory->subcategory_name,
                            'volume' => $subcategory->volume,
                            'unit' => $subcategory->unit,
                            'unit_price' => $subcategory->unit_price,
                            'items' => $subcategory->items->map(function ($item) {
                                return [
                                    'item_description' => $item->item_description,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }
}
