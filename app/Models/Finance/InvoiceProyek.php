<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Finance\PaymentProof;
use App\Services\Finance\InvoiceCalculatorService;

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

    protected function getCalculator(): InvoiceCalculatorService
    {
        return app(InvoiceCalculatorService::class);
    }

    /**
     * Bukti pembayaran untuk invoice proyek.
     */
    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class, 'invoice_number', 'invoice_number')
            ->where('invoice_type', 'proyek')
            ->orderByDesc('created_at');
    }

    /**
     * Total pembayaran dari bukti pembayaran yang sudah masuk.
     */
    public function getTotalPaidAmount(): int
    {
        return $this->getCalculator()->getTotalPaidAmount($this);
    }

    /**
     * Tahap pembayaran yang disusun dari bukti pembayaran.
     */
    public function getPaymentInstallmentsAttribute($value): array
    {
        $paymentProofs = $this->relationLoaded('paymentProofs')
            ? $this->paymentProofs
            : $this->paymentProofs()->get();

        if ($paymentProofs->isNotEmpty()) {
            return $paymentProofs
                ->sortBy(fn($paymentProof) => sprintf('%06d-%010d', (int) ($paymentProof->payment_stage ?? 0), (int) ($paymentProof->created_at?->timestamp ?? 0)))
                ->values()
                ->map(function ($paymentProof) {
                    return [
                        'label' => 'Pembayaran Ke ' . ($paymentProof->payment_stage ?? '-'),
                        'amount' => (int) ($paymentProof->amount ?? 0),
                    ];
                })
                ->all();
        }

        if (empty($value)) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Calculate discount amount based on total amount
     */
    public function getDiscountAmount(float $totalAmount = null): float
    {
        return $this->getCalculator()->calculateDiscountAmount(
            $totalAmount ?? (float) ($this->total_amount ?? 0),
            $this->discount_type,
            $this->discount_value ? (float) $this->discount_value : null
        );
    }

    /**
     * Calculate DP amount based on total after discount
     */
    public function getDpAmount(float $baseAmount = null): float
    {
        return $this->getCalculator()->calculateDpAmount(
            (float) ($this->total_amount ?? 0),
            $this->total_after_discount,
            $this->dp_type,
            $this->dp_value ? (float) $this->dp_value : null,
            $baseAmount
        );
    }

    /**
     * Total invoice setelah diskon.
     */
    public function getNetAmount(): int
    {
        return $this->getCalculator()->calculateNetAmount(
            $this->total_after_discount,
            $this->total_amount
        );
    }

    /**
     * Sisa pembayaran yang harus dilunasi.
     */
    public function getRemainingAmount(): int
    {
        return $this->getCalculator()->calculateRemainingAmount(
            $this->getNetAmount(),
            $this->getTotalPaidAmount()
        );
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
        return $this->getCalculator()->isFullyPaid(
            $this->getNetAmount(),
            $this->getTotalPaidAmount()
        );
    }

    /**
     * Label status pembayaran invoice proyek.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->isFullyPaid() ? 'Lunas' : 'Belum Lunas';
    }

    /**
     * Badge class untuk status pembayaran invoice proyek.
     */
    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return $this->isFullyPaid()
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';
    }
}
