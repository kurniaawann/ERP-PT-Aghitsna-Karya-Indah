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
        'incoming_payment',
        'amount_in_words',
        'selected_payment_accounts',
        'signed_by',
        'division',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'integer',
        'incoming_payment' => 'integer',
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

    public function miscellaneousCosts()
    {
        return $this->hasMany(RABMiscellaneousCost::class, 'rab_number', 'rab_number')
            ->orderBy('item_order');
    }

    // ─── Static Methods ────────────────────────────────────────────────────────

    public static function generateRABNumber()
    {
        $year = date('Y');
        $romanMonth = self::arabicToRoman((int) date('n'));
        $sequence = self::getNextSequenceNumber();
        return "{$sequence}/RAB/{$romanMonth}/{$year}";
    }

    public static function getNextSequenceNumber()
    {
        $latestSequence = self::max('sequence_number') ?? 0;
        return $latestSequence + 1;
    }

    private static function arabicToRoman(int $num): string
    {
        $map = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        return $map[$num] ?? '';
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
                        $legacySubtotal = (int) ($subcategory->sub_harga ?? 0);
                        $legacyVolume = $subcategory->volume;
                        $legacyUnit = $subcategory->unit;
                        $legacyUnitPrice = $subcategory->unit_price;

                        return [
                            'subcategory_name' => $subcategory->subcategory_name,
                            'sub_harga' => $subcategory->items->sum(function ($item) {
                                return (int) ($item->sub_harga ?? 0);
                            }) ?: $legacySubtotal,
                            'items' => $subcategory->items->values()->map(function ($item, $index) use ($legacyVolume, $legacyUnit, $legacyUnitPrice, $legacySubtotal) {
                                $hasItemPricing = $item->volume !== null
                                    || $item->unit !== null
                                    || $item->unit_price !== null
                                    || $item->sub_harga !== null;

                                $useLegacyPricing = !$hasItemPricing && $index === 0;

                                return [
                                    'item_description' => $item->item_description,
                                    'volume' => $hasItemPricing ? $item->volume : ($useLegacyPricing ? $legacyVolume : null),
                                    'unit' => $hasItemPricing ? $item->unit : ($useLegacyPricing ? $legacyUnit : null),
                                    'unit_price' => $hasItemPricing ? $item->unit_price : ($useLegacyPricing ? $legacyUnitPrice : null),
                                    'sub_harga' => $hasItemPricing ? $item->sub_harga : ($useLegacyPricing ? $legacySubtotal : 0),
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }
}
