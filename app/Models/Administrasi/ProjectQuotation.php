<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\PaymentAccount;

/**
 * Model untuk data Penawaran Proyek (Project Quotation).
 *
 * Model ini menyimpan data header penawaran proyek,
 * termasuk nomor penawaran, tanggal, penerima, total, dan relasi ke items.
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
        'recipient',
        'recipient_address',
        'total_amount',
        'amount_in_words',
        'selected_payment_accounts',
        'signed_by',
        'division',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'integer',
        'sequence_number' => 'integer',
        'selected_payment_accounts' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'quotation_number';
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Relasi ke items penawaran.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(ProjectQuotationItem::class, 'quotation_number', 'quotation_number')
            ->orderBy('order_number');
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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate nomor penawaran berikutnya: {n}/{n}/PT.AKI/{yy}
     * Contoh: 1/1/PT.AKI/25
     *
     * @return string  Nomor penawaran yang sudah di-generate
     */
    public static function generateQuotationNumber(): string
    {
        $year = date('y');

        $last = self::where('quotation_number', 'like', "%/PT.AKI/{$year}")
            ->orderBy('sequence_number', 'desc')
            ->first();

        $next = $last ? ($last->sequence_number + 1) : 1;

        return "{$next}/{$next}/PT.AKI/{$year}";
    }

    /**
     * Mendapatkan nomor urut (sequence) berikutnya untuk tahun berjalan.
     *
     * @return int  Nomor urut berikutnya
     */
    public static function getNextSequenceNumber(): int
    {
        $year = date('y');
        $last = self::where('quotation_number', 'like', "%/PT.AKI/{$year}")
            ->orderBy('sequence_number', 'desc')
            ->first();
        return $last ? ($last->sequence_number + 1) : 1;
    }
}
