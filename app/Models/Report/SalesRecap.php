<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\PaymentProof;

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
     * Bukti pembayaran untuk rekap penjualan ini.
     */
    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class, 'sales_recap_id', 'id_sales_recap');
    }

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
            $totalCapital += $this->normalizeCurrencyInput($item['capital_price'] ?? 0) * (int) ($item['quantity'] ?? 0);
            $totalSelling += $this->normalizeCurrencyInput($item['selling_price'] ?? 0) * (int) ($item['quantity'] ?? 0);
        }

        $this->total_capital = $totalCapital;
        $this->total_selling = $totalSelling;
        $this->total_profit = $totalSelling - $totalCapital;
    }

    /**
     * Normalisasi harga agar string rupiah tidak memutus perhitungan.
     */
    private function normalizeCurrencyInput($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = (string) $value;
        $value = preg_replace('/[^0-9,\.\-]/', '', $value);

        if ($value === '' || $value === null) {
            return 0.0;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace('.', '', $value);
        }

        return (float) $value;
    }
}
