<?php

namespace App\Models\Administrasi;

use Carbon\Carbon;
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
 * {keterangan, volume, satuan, harga}) serta discount opsional.
 *
 * Design separasi ketat: tabel ini hanya menyimpan apa yang dibutuhkan
 * PDF penawaran. PPN & DP adalah konsep penagihan — hanya milik invoice.
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
        'city',
        'proyek',
        'total_amount',
        'items',
        'items_mode',
        'free_text',
        'discount_type',
        'discount_value',
        'total_after_discount',
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
     * Total akhir penawaran (setelah diskon) untuk ditampilkan & diterbilangkan.
     *
     * Aturan sama dengan service saat menyimpan total_after_discount: diskon
     * hanya dipotong bila > 0 dan lebih kecil dari total.
     */
    public function getFinalTotal(): int
    {
        $total = (int) ($this->total_amount ?? 0);
        $discount = (int) $this->getDiscountAmount();

        return ($discount > 0 && $discount < $total) ? $total - $discount : $total;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate nomor penawaran berikutnya: {A}/{B}/PT.AKI/{bulan romawi}/{yy}
     * Kedua angka (A dan B) diincrement secara terpisah.
     * Contoh: 275/310/PT.AKI/VIII/26 -> 276/311/PT.AKI/VIII/26
     *
     * Revisi klien: bulan romawi (dari tanggal penawaran) dicantumkan sebelum
     * tahun. Nomor format lama ({A}/{B}/PT.AKI/{yy}) tetap ikut dihitung agar
     * urutan di tahun yang sama berlanjut.
     *
     * @param  \DateTimeInterface|string|null  $date  Tanggal penawaran (default hari ini)
     * @return string  Nomor penawaran yang sudah di-generate
     */
    public static function generateQuotationNumber($date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();
        $last = self::lastSequenceOfYear($date->format('y'));

        if ($last) {
            [$nextA, $nextB] = [$last[0] + 1, $last[1] + 1];
        } else {
            $nextA = 275;
            $nextB = 310;
        }

        return "{$nextA}/{$nextB}/PT.AKI/" . bulan_romawi($date->month) . '/' . $date->format('y');
    }

    /**
     * Mendapatkan nomor urut (sequence) berikutnya untuk tahun berjalan.
     * Sequence mengikuti angka pertama (A) dari quotation_number.
     *
     * @return int  Nomor urut berikutnya
     */
    public static function getNextSequenceNumber(): int
    {
        $last = self::lastSequenceOfYear(date('y'));

        return $last ? $last[0] + 1 : 1;
    }

    /**
     * Pasangan angka [A, B] terbesar dari nomor penawaran pada tahun tertentu
     * (dibandingkan sebagai angka, bukan string — panjang bulan romawi
     * berbeda-beda sehingga urutan string tidak bisa dipakai).
     *
     * @param  string  $year  Tahun 2 digit
     * @return array{0: int, 1: int}|null
     */
    private static function lastSequenceOfYear(string $year): ?array
    {
        return self::where('quotation_number', 'like', '%/PT.AKI/%')
            ->where('quotation_number', 'like', "%/{$year}")
            ->pluck('quotation_number')
            ->map(fn (string $number) => preg_match('/^(\d+)\/(\d+)\//', $number, $m) ? [(int) $m[1], (int) $m[2]] : null)
            ->filter()
            ->sortByDesc(fn (array $pair) => $pair[0])
            ->first();
    }
}
