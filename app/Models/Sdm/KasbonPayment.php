<?php

namespace App\Models\Sdm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for the kasbon_payments table.
 *
 * Stores individual payment records for kasbon installments.
 * Each payment represents one installment toward a kasbon's total amount.
 *
 * @property int $id
 * @property string $kasbon_code FK to kasbons.kasbon_code
 * @property int|null $payroll_id FK to payrolls.id (if deducted from payroll)
 * @property int|null $salary_slip_id FK to salary_slips.id (if deducted from slip gaji)
 * @property int $amount Payment amount for this installment
 * @property string $payment_method 'manual' or 'payroll_deduction'
 * @property \Carbon\Carbon $payment_date
 * @property string|null $notes
 * @property-read \App\Models\Sdm\Kasbon $kasbon
 * @property-read \App\Models\Sdm\Payroll|null $payroll
 */
class KasbonPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'kasbon_code',
        'payroll_id',
        'salary_slip_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_date' => 'date',
    ];

    // ─── Relationships ──────────────────────────────────────────────────

    public function kasbon(): BelongsTo
    {
        return $this->belongsTo(Kasbon::class, 'kasbon_code');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function salarySlip(): BelongsTo
    {
        return $this->belongsTo(SalarySlip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Accessors ──────────────────────────────────────────────────────

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return $this->payment_method === 'manual' ? 'Bayar Tunai' : 'Potong Gaji';
    }
}
