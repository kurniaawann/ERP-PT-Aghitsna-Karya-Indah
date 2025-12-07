<?php

namespace App\Models\Invoice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceAlumunium extends Model
{
    use HasFactory;

    protected $table = 'alumunium_invoices';
    protected $primaryKey = 'invoice_number'; // Set primary key to invoice_number
    public $incrementing = false; // Non-incrementing primary key
    protected $keyType = 'string'; // Primary key is string type
    public $timestamps = true;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'recipient',
        'regarding',
        'project_description',
        'items',
        'total_amount',
        'discount_type',
        'discount_value',
        'total_after_discount',
        'dp_type',
        'dp_value',
        'dp_amount',
        'selected_payment_accounts',
    ];

    protected $casts = [
        'items' => 'json',
        'selected_payment_accounts' => 'json',
        'total_amount' => 'integer',
        'total_after_discount' => 'integer',
        'dp_amount' => 'integer',
        'discount_value' => 'decimal:2',
        'dp_value' => 'decimal:2',
        'invoice_date' => 'date',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'invoice_number';
    }

    /**
     * Calculate discount amount based on total and discount settings.
     *
     * @param float $totalAmount
     * @return float
     */
    public function getDiscountAmount(float $totalAmount): float
    {
        if (!$this->discount_value || $this->discount_value <= 0) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return round(($totalAmount * $this->discount_value) / 100);
        }

        return round($this->discount_value);
    }

    /**
     * Calculate DP amount based on total after discount and DP settings.
     *
     * @param float $totalAfterDiscount
     * @return float
     */
    public function getDpAmount(float $totalAfterDiscount): float
    {
        if (!$this->dp_value || $this->dp_value <= 0) {
            return 0;
        }

        if ($this->dp_type === 'percentage') {
            return round(($totalAfterDiscount * $this->dp_value) / 100);
        }

        return round($this->dp_value);
    }
}
