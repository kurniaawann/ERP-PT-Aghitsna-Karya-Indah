<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Finance\PaymentProof;
use App\Models\Sdm\Division;
use App\Models\Sdm\Executive;
use App\Services\Finance\InvoiceCalculatorService;
use App\Services\Finance\PaymentProofService;

class InvoiceAlumunium extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($invoice) {
            foreach ($invoice->paymentProofs as $proof) {
                app(PaymentProofService::class)->delete($proof->file_path);
                $proof->delete();
            }
        });
    }

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
        'signed_by_id',
        'division_id',
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
     * Relasi ke petinggi (Nama Penandatangan) yang menandatangani invoice.
     *
     * Diambil dari data petinggi (executives) melalui foreign key signed_by_id.
     *
     * @return BelongsTo
     */
    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(Executive::class, 'signed_by_id');
    }

    /**
     * Relasi ke divisi penandatangan invoice.
     *
     * Diambil dari submodul divisi (divisions) melalui foreign key division_id.
     *
     * @return BelongsTo
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

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
     * Bukti pembayaran untuk invoice alumunium.
     */
    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class, 'invoice_number', 'invoice_number')
            ->where('invoice_type', 'alumunium')
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
     * Total keseluruhan invoice (grand total).
     */
    public function getNetAmount(): int
    {
        return $this->getCalculator()->getNetAmount($this);
    }

    /**
     * Sisa tagihan: (total_amount - discount) - dp - total_payment.
     */
    public function getRemainingAmount(): int
    {
        return $this->getCalculator()->getRemainingAmount($this);
    }

    /**
     * Cek apakah invoice sudah lunas.
     */
    public function isFullyPaid(): bool
    {
        return $this->getCalculator()->isFullyPaidForInvoice($this);
    }

    /**
     * Label status pembayaran invoice alumunium.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->isFullyPaid() ? 'Sudah Lunas' : 'Belum Lunas';
    }

    /**
     * Badge class untuk status pembayaran invoice alumunium.
     */
    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return $this->isFullyPaid()
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';
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
}
