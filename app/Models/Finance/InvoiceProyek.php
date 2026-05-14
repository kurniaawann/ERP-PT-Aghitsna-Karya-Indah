<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InvoiceProyek extends Model
{
    use HasFactory;

    protected $table = 'proyek_invoices';
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
        'payment_installments',
        'selected_payment_accounts',
    ];

    protected $casts = [
        'items' => 'json',
        'payment_installments' => 'json',
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
     * Calculate discount amount based on total amount
     */
    public function getDiscountAmount(float $totalAmount = null): float
    {
        if (!$this->discount_value || $this->discount_value <= 0) {
            return 0;
        }

        $amount = $totalAmount ?? $this->total_amount;

        if ($this->discount_type === 'percentage') {
            return round(($amount * floatval($this->discount_value)) / 100);
        }

        return round(floatval($this->discount_value));
    }

    /**
     * Calculate DP amount based on total after discount
     */
    public function getDpAmount(float $baseAmount = null): float
    {
        if (!$this->dp_value || $this->dp_value <= 0) {
            return 0;
        }

        $amount = $baseAmount ?? ($this->total_after_discount ?? $this->total_amount);

        if ($this->dp_type === 'percentage') {
            return round(($amount * floatval($this->dp_value)) / 100);
        }

        return round(floatval($this->dp_value));
    }

    /**
     * Total invoice setelah diskon.
     */
    public function getNetAmount(): int
    {
        return (int) max(0, $this->total_after_discount ?? $this->total_amount ?? 0);
    }

    /**
     * Total pembayaran yang sudah masuk (DP + cicilan).
     */
    public function getTotalPaidAmount(): int
    {
        $totalPaid = (int) ($this->dp_amount ?? 0);
        $paymentInstallments = $this->payment_installments;

        if (is_string($paymentInstallments)) {
            $paymentInstallments = json_decode($paymentInstallments, true);
        }

        if (is_array($paymentInstallments)) {
            foreach ($paymentInstallments as $installment) {
                $totalPaid += (int) ($installment['amount'] ?? 0);
            }
        }

        return (int) max(0, $totalPaid);
    }

    /**
     * Sisa pembayaran yang harus dilunasi.
     */
    public function getRemainingAmount(): int
    {
        return (int) max(0, $this->getNetAmount() - $this->getTotalPaidAmount());
    }

    /**
     * Tanggal jatuh tempo invoice proyek.
     */
    public function getDueDate(): Carbon
    {
        return Carbon::parse($this->invoice_date)->addMonthNoOverflow();
    }

    /**
     * Cek apakah invoice sudah lunas.
     */
    public function isFullyPaid(): bool
    {
        return $this->getTotalPaidAmount() >= $this->getNetAmount();
    }
}
