<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Finance\PaymentAccount;
use App\Models\Sdm\Executive;
use App\Models\Sdm\Division;
use App\Models\User;

/**
 * Model untuk data Penawaran Proyek (Project Quotation).
 *
 * Model ini menyimpan data header penawaran proyek, termasuk nomor
 * penawaran, tanggal, penerima, total, dan items (format flat JSON
 * {keterangan, volume, satuan, harga}) serta discount, DP, dan PPN opsional.
 *
 * Primary key: quotation_number (string, bukan auto-increment)
 */
class ProjectQuotation extends Model
{
    use HasFactory;

    protected $table = 'project_quotations';
    protected $primaryKey = 'quotation_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'quotation_number',
        'sequence_number',
        'date',
        'subject',
        'attachment',
        'recipient',
        'project_description',
        'location',
        'total_amount',
        'items',
        'discount_type',
        'discount_value',
        'ppn',
        'total_after_discount',
        'dp_type',
        'dp_value',
        'dp_amount',
        'amount_in_words',
        'selected_payment_accounts',
        'signed_by_id',
        'division_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'integer',
        'sequence_number' => 'integer',
        'items' => 'json',
        'total_after_discount' => 'integer',
        'discount_value' => 'decimal:2',
        'ppn' => 'decimal:2',
        'dp_value' => 'decimal:2',
        'dp_amount' => 'integer',
        'selected_payment_accounts' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'quotation_number';
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Invoice Proyek yang dibuat otomatis dari penawaran ini.
     *
     * Relasi inverse dari InvoiceProyek::quotation(). Berdasarkan design
     * snapshot, invoice tetap ada meski penawaran dihapus (FK ON DELETE SET
     * NULL), sehingga relasi ini bisa bernilai kosong setelah penawaran
     * dihapus.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(\App\Models\Finance\InvoiceProyek::class, 'quotation_number', 'quotation_number');
    }

    /**
     * Mendapatkan rekening pembayaran berdasarkan selected_payment_accounts.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function paymentAccounts()
    {
        $ids = $this->selected_payment_accounts ?? [];
        if (empty($ids)) {
            return PaymentAccount::active()->get();
        }
        return PaymentAccount::whereIn('id', $ids)->orderBy('id')->get();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Menghitung jumlah discount berdasarkan total amount.
     */
    public function getDiscountAmount(float $totalAmount = null): float
    {
        return app(\App\Services\Finance\InvoiceCalculatorService::class)->calculateDiscountAmount(
            $totalAmount ?? (float) ($this->total_amount ?? 0),
            $this->discount_type,
            $this->discount_value ? (float) $this->discount_value : null
        );
    }

    /**
     * Menghitung jumlah PPN berdasarkan total setelah diskon (dasar pengenaan).
     *
     * PPN disimpan sebagai persentase. Base PPN = total_after_discount jika
     * ada, selain itu fallback ke total_amount.
     *
     * @return float  Jumlah PPN
     */
    public function getPpnAmount(): float
    {
        if (!$this->ppn || (float) $this->ppn <= 0) {
            return 0;
        }

        $base = ($this->total_after_discount !== null && (float) $this->total_after_discount !== (float) ($this->total_amount ?? 0))
            ? (float) $this->total_after_discount
            : (float) ($this->total_amount ?? 0);

        return round(($base * (float) $this->ppn) / 100);
    }

    /**
     * Menghitung jumlah DP berdasarkan total setelah diskon.
     *
     * DP dihitung dari total SETELAH diskon (jika ada), selain itu fallback
     * ke total_amount — pola yang sama dengan InvoiceProyek.
     *
     * @param  float|null  $baseAmount  Base amount untuk perhitungan DP (opsional)
     * @return float  Jumlah DP
     */
    public function getDpAmount(float $baseAmount = null): float
    {
        return app(\App\Services\Finance\InvoiceCalculatorService::class)->calculateDpAmount(
            (float) ($this->total_amount ?? 0),
            $this->total_after_discount,
            $this->dp_type,
            $this->dp_value ? (float) $this->dp_value : null,
            $baseAmount
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate nomor penawaran berikutnya: {A}/{B}/PT.AKI/{yy}
     * Kedua angka (A dan B) diincrement secara terpisah.
     * Contoh: 275/310/PT.AKI/26 -> 276/311/PT.AKI/26
     *
     * @return string  Nomor penawaran yang sudah di-generate
     */
    public static function generateQuotationNumber(): string
    {
        $year = date('y');

        $last = self::where('quotation_number', 'like', "%/PT.AKI/{$year}")
            ->orderByDesc('quotation_number')
            ->first();

        if ($last && preg_match('/^(\d+)\/(\d+)\//', $last->quotation_number, $matches)) {
            $nextA = (int) $matches[1] + 1;
            $nextB = (int) $matches[2] + 1;
        } else {
            $nextA = 275;
            $nextB = 310;
        }

        return "{$nextA}/{$nextB}/PT.AKI/{$year}";
    }

    /**
     * Mendapatkan nomor urut (sequence) berikutnya untuk tahun berjalan.
     * Sequence mengikuti angka pertama (A) dari quotation_number.
     *
     * @return int  Nomor urut berikutnya
     */
    public static function getNextSequenceNumber(): int
    {
        $year = date('y');
        $last = self::where('quotation_number', 'like', "%/PT.AKI/{$year}")
            ->orderByDesc('quotation_number')
            ->first();

        if ($last && preg_match('/^(\d+)\//', $last->quotation_number, $matches)) {
            return (int) $matches[1] + 1;
        }

        return 1;
    }
}
