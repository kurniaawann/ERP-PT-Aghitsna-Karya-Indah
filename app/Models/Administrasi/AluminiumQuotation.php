<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Finance\PaymentAccount;
use App\Models\Sdm\Executive;
use App\Models\Sdm\Division;

class AluminiumQuotation extends Model
{
    use HasFactory;

    protected $table = 'aluminium_quotations';
    protected $primaryKey = 'quotation_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'quotation_number',
        'sequence_number',
        'date',
        'subject',
        'recipient',
        'project_description',
        'total_amount',
        'items',
        'discount_type',
        'discount_value',
        'total_after_discount',
        'dp_type',
        'dp_value',
        'dp_amount',
        'amount_in_words',
        'selected_payment_accounts',
        'signed_by_id',
        'division_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'integer',
        'sequence_number' => 'integer',
        'items' => 'json',
        'total_after_discount' => 'integer',
        'dp_amount' => 'integer',
        'discount_value' => 'decimal:2',
        'dp_value' => 'decimal:2',
        'selected_payment_accounts' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'quotation_number';
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function groups()
    {
        return $this->hasMany(AluminiumQuotationGroup::class, 'quotation_number', 'quotation_number')
            ->orderBy('order_number');
    }

    /**
     * Invoice Alumunium yang dibuat otomatis dari penawaran ini.
     *
     * Relasi inverse dari InvoiceAlumunium::quotation(). Berdasarkan design
     * snapshot, invoice tetap ada meski penawaran dihapus (FK ON DELETE SET
     * NULL), sehingga relasi ini bisa bernilai kosong setelah penawaran
     * dihapus.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(\App\Models\Finance\InvoiceAlumunium::class, 'quotation_number', 'quotation_number');
    }

    public function paymentAccounts()
    {
        $ids = $this->selected_payment_accounts ?? [];
        if (empty($ids)) {
            return PaymentAccount::active()->get();
        }
        return PaymentAccount::whereIn('id', $ids)->orderBy('id')->get();
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(Executive::class, 'signed_by_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    // ─── Calculator Helpers ───────────────────────────────────────────────────

    protected function getCalculator(): \App\Services\Finance\InvoiceCalculatorService
    {
        return app(\App\Services\Finance\InvoiceCalculatorService::class);
    }

    /**
     * Menghitung jumlah discount berdasarkan total amount.
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
     * Menghitung jumlah DP berdasarkan total setelah discount.
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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate next quotation number: {A}/{B}/ALU/{yy}
     * Kedua angka (A dan B) diincrement secara terpisah.
     * e.g. 275/310/ALU/26 -> 276/311/ALU/26
     */
    public static function generateQuotationNumber(): string
    {
        $year = date('y');

        $last = self::where('quotation_number', 'like', "%/ALU/{$year}")
            ->orderByDesc('quotation_number')
            ->first();

        if ($last && preg_match('/^(\d+)\/(\d+)\//', $last->quotation_number, $matches)) {
            $nextA = (int) $matches[1] + 1;
            $nextB = (int) $matches[2] + 1;
        } else {
            $nextA = 275;
            $nextB = 310;
        }

        return "{$nextA}/{$nextB}/ALU/{$year}";
    }

    /**
     * Mendapatkan nomor urut (sequence) berikutnya.
     * Sequence mengikuti angka pertama (A) dari quotation_number.
     */
    public static function getNextSequenceNumber(): int
    {
        $year = date('y');
        $last = self::where('quotation_number', 'like', "%/ALU/{$year}")
            ->orderByDesc('quotation_number')
            ->first();

        if ($last && preg_match('/^(\d+)\//', $last->quotation_number, $matches)) {
            return (int) $matches[1] + 1;
        }

        return 1;
    }
}
