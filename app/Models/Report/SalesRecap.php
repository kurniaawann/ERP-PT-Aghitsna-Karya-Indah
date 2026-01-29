<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesRecap extends Model
{
    use HasFactory;

    protected $table = 'sales_recaps';
    protected $primaryKey = 'id_sales_recap';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sales_recap',
        'date',
        'name_proyek',
        'items',
        'total_capital',
        'total_selling',
        'total_profit',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'items' => 'json',
        'total_capital' => 'integer',
        'total_selling' => 'integer',
        'total_profit' => 'integer',
    ];

    /**
     * Check if status is Lunas
     */
    public function isLunas()
    {
        return $this->status === 'Lunas';
    }

    /**
     * Check if can be edited (not Lunas)
     */
    public function canBeEdited()
    {
        return !$this->isLunas();
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals()
    {
        $items = is_string($this->items) ? json_decode($this->items, true) : $this->items;

        $totalCapital = 0;
        $totalSelling = 0;

        foreach ($items as $item) {
            $totalCapital += ($item['capital_price'] ?? 0) * ($item['quantity'] ?? 0);
            $totalSelling += ($item['selling_price'] ?? 0) * ($item['quantity'] ?? 0);
        }

        $this->total_capital = $totalCapital;
        $this->total_selling = $totalSelling;
        $this->total_profit = $totalSelling - $totalCapital;
    }
}
