<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Finance\PaymentProof;
use App\Models\User;
use App\Services\Finance\InvoiceCalculatorService;

/**
 * Model untuk Invoice Proyek.
 *
 * Mengelola data invoice proyek dengan primary key berupa string (invoice_number).
 * Menghitung total, diskon, DP, sisa tagihan, dan status pembayaran
 * melalui relasi PaymentProof dan InvoiceCalculatorService.
 */
class InvoiceProyek extends Model
{
    use HasFactory;

    protected $table = 'proyek_invoices';
    protected $primaryKey = 'invoice_number';
    public $incrementing = false;
    protected $keyType = 'string';
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
        'created_by',
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
     * Mendapatkan route key name untuk model binding.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'invoice_number';
    }

    /**
     * Mendapatkan instance InvoiceCalculatorService dari service container.
     *
     * @return \App\Services\Finance\InvoiceCalculatorService
     */
    protected function getCalculator(): InvoiceCalculatorService
    {
        return app(InvoiceCalculatorService::class);
    }

    /**
     * Relasi ke bukti pembayaran (PaymentProof) untuk invoice ini.
     *
     * Filter hanya untuk tipe 'proyek' dan urutkan berdasarkan.created_at descending.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class, 'invoice_number', 'invoice_number')
            ->where('invoice_type', 'proyek')
            ->orderByDesc('created_at');
    }

    /**
     * Relasi ke user yang membuat invoice ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mendapatkan total pembayaran yang sudah masuk dari bukti pembayaran.
     *
     * @return int  Total nominal yang sudah dibayar
     */
    public function getTotalPaidAmount(): int
    {
        return $this->getCalculator()->getTotalPaidAmount($this);
    }

    /**
     * Accessor untuk payment_installments.
     *
     * Mengambil data cicilan pembayaran dari relasi paymentProofs.
     * Jika tidak ada bukti pembayaran, fallback ke data JSON yang tersimpan.
     *
     * @param  mixed  $value  Value dari database
     * @return array  Daftar cicilan pembayaran
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
     * Menghitung jumlah diskon berdasarkan total amount.
     *
     * @param  float|null  $totalAmount  Total amount (opsional, default: total_amount model)
     * @return float  Jumlah diskon
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
     * Menghitung jumlah DP berdasarkan total setelah diskon.
     *
     * @param  float|null  $baseAmount  Base amount untuk perhitungan DP (opsional)
     * @return float  Jumlah DP
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
     * Mendapatkan grand total invoice (total_amount).
     *
     * @return int  Grand total
     */
    public function getNetAmount(): int
    {
        return $this->getCalculator()->getNetAmount($this);
    }

    /**
     * Menghitung sisa tagihan: (total_amount - discount) - dp - total_payment.
     *
     * @return int  Sisa tagihan (tidak pernah negatif)
     */
    public function getRemainingAmount(): int
    {
        return $this->getCalculator()->getRemainingAmount($this);
    }

    /**
     * Mendapatkan tanggal jatuh tempo (1 bulan dari tanggal invoice).
     *
     * @return \Carbon\Carbon
     */
    public function getDueDate(): Carbon
    {
        return Carbon::parse($this->invoice_date)->addMonthNoOverflow();
    }

    /**
     * Mengecek apakah invoice sudah lunas.
     *
     * @return bool
     */
    public function isFullyPaid(): bool
    {
        return $this->getCalculator()->isFullyPaidForInvoice($this);
    }

    /**
     * Accessor untuk label status pembayaran.
     *
     * @return string  'Lunas' atau 'Belum Lunas'
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->isFullyPaid() ? 'Sudah Lunas' : 'Belum Lunas';
    }

    /**
     * Accessor untuk CSS badge class status pembayaran.
     *
     * @return string  Tailwind CSS class
     */
    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return $this->isFullyPaid()
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';
    }
}
