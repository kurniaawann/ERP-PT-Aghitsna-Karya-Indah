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
        'project_description',
        'total_amount',
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
