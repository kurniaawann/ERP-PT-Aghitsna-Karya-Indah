<?php

namespace App\Models\Administrasi;

use App\Models\Finance\InvoiceAlumunium;
use App\Models\Finance\InvoiceBarang;
use App\Models\Finance\InvoiceProyek;
use App\Models\Finance\PaymentAccount;
use App\Models\Finance\PaymentProof;
use App\Models\Sdm\Executive;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk modul Kwitansi.
 *
 * Model ini merepresentasikan data kwitansi (tanda bukti penerimaan uang)
 * yang dikeluarkan oleh perusahaan, termasuk informasi penerima, jumlah,
 * sisa, tanggal, bank (opsional), dan lokasi.
 *
 * Primary key menggunakan string (id_kwintansi) dengan format XX/AKI/YY.
 * Contoh: 01/AKI/25, 02/AKI/25, dst.
 */
class Kwintansi extends Model
{
    use HasFactory;

    /**
     * Nama tabel database.
     */
    protected $table = 'kwintansi';

    /**
     * Nama kolom primary key (string, bukan auto-increment).
     */
    protected $primaryKey = 'id_kwintansi';

    /**
     * Primary key bukan auto-increment (dibuat manual via generateKwintansiCode).
     */
    public $incrementing = false;

    /**
     * Tipe data primary key adalah string.
     */
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara mass-assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_kwintansi',
        'amount',
        'payment_account_id',
        'selected_payment_accounts',
        'include_bank',
        'is_tunai',
        'is_cheque',
        'is_bilyet_giro',
        'received_from',
        'payment_for',
        'remaining',
        'kwintansi_date',
        'location',
        'invoice_number',
        'invoice_type',
        'payment_proof_id',
        'signed_by_id',
        'created_by',
    ];

    /**
     * Konversi otomatis tipe data kolom.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'kwintansi_date' => 'date',
        'include_bank' => 'boolean',
        'is_tunai' => 'boolean',
        'is_cheque' => 'boolean',
        'is_bilyet_giro' => 'boolean',
        'amount' => 'integer',
        'remaining' => 'integer',
        'selected_payment_accounts' => 'array',
    ];

    /**
     * Relasi ke model PaymentAccount (bank).
     *
     * @return BelongsTo
     */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    /**
     * Relasi ke Invoice Proyek yang menjadi sumber kwitansi (auto-generate).
     *
     * @return BelongsTo
     */
    public function invoiceProyek(): BelongsTo
    {
        return $this->belongsTo(InvoiceProyek::class, 'invoice_number', 'invoice_number');
    }

    /**
     * Relasi ke Invoice Alumunium yang menjadi sumber kwitansi (auto-generate).
     *
     * @return BelongsTo
     */
    public function invoiceAlumunium(): BelongsTo
    {
        return $this->belongsTo(InvoiceAlumunium::class, 'invoice_number', 'invoice_number');
    }

    /**
     * Relasi ke Invoice Barang yang menjadi sumber kwitansi (auto-generate).
     *
     * @return BelongsTo
     */
    public function invoiceBarang(): BelongsTo
    {
        return $this->belongsTo(InvoiceBarang::class, 'invoice_number', 'invoice_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke penandatangan (petinggi) kwitansi.
     *
     * Kwitansi manual memilih Nama Tanda Tangan dari data petinggi
     * (executives). Kwitansi otomatis mengikuti penandatangan invoice sumber.
     *
     * @return BelongsTo
     */
    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(Executive::class, 'signed_by_id');
    }

    /**
     * Relasi ke bukti pembayaran (PaymentProof) sumber auto-generate kwitansi.
     *
     * @return BelongsTo
     */
    public function paymentProof(): BelongsTo
    {
        return $this->belongsTo(PaymentProof::class, 'payment_proof_id');
    }

    /**
     * Accessor untuk nomor urut pembayaran per invoice.
     *
     * "No." pada kwitansi menampilkan urutan pembayaran untuk invoice terkait
     * (001 = pembayaran pertama, 002 = pembayaran kedua, dst), bukan nomor
     * unik. Urutan dihitung berdasarkan urutan pembuatan kwitansi pada
     * invoice yang sama. Kwitansi manual (tanpa invoice) bernilai null.
     *
     * @return int|null
     */
    public function getPaymentSequenceAttribute(): ?int
    {
        if (empty($this->invoice_number)) {
            return null;
        }

        $query = static::query()
            ->where('invoice_number', $this->invoice_number);

        if (!empty($this->invoice_type)) {
            $query->where('invoice_type', $this->invoice_type);
        }

        $orderedIds = $query
            ->orderBy('created_at')
            ->orderBy('id_kwintansi')
            ->pluck('id_kwintansi');

        $position = $orderedIds->search($this->id_kwintansi);

        return $position === false ? null : (int) $position + 1;
    }

    /**
     * Accessor untuk total uang masuk (total terbayar) kumulatif per tanggal kwitansi.
     *
     * Menjumlahkan seluruh bukti pembayaran (PaymentProof) invoice proyek
     * terkait yang dibuat sampai dengan tanggal kwitansi ini. Nilai ini sama
     * dengan "Terbayar" yang tampil pada invoice proyek.
     *
     * @return int
     */
    public function getTotalAccumulatedAttribute(): int
    {
        if (empty($this->invoice_number)) {
            return 0;
        }

        return (int) PaymentProof::query()
            ->where('invoice_type', 'proyek')
            ->where('invoice_number', $this->invoice_number)
            ->whereDate('created_at', '<=', $this->kwintansi_date?->toDateString())
            ->sum('amount');
    }

    /**
     * Generate kode kwitansi berikutnya dengan format XX/AKI/YY.
     *
     * Contoh: 01/AKI/25, 02/AKI/25, ..., 99/AKI/25, 100/AKI/25, dst.
     * Kode di-reset setiap tahun (YY = 2 digit tahun).
     *
     * @return string Kode kwitansi baru (contoh: 01/AKI/25)
     */
    public static function generateKwintansiCode(): string
    {
        $year = date('y'); // 25 untuk 2025

        // Ambil kwintansi terakhir untuk tahun ini
        $lastKwintansi = self::where('id_kwintansi', 'like', "%/AKI/{$year}")
            ->orderBy('id_kwintansi', 'desc')
            ->first();

        if (! $lastKwintansi) {
            return "01/AKI/{$year}";
        }

        // Ambil nomor dari kode terakhir (contoh: 01/AKI/25 -> 01)
        $parts = explode('/', $lastKwintansi->id_kwintansi);
        $lastNumber = (int) $parts[0];
        $newNumber = $lastNumber + 1;

        // Format dengan 2 digit (01, 02, ..., 99, 100, dst)
        return str_pad($newNumber, 2, '0', STR_PAD_LEFT)."/AKI/{$year}";
    }
}
