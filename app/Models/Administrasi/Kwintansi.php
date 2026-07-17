<?php

namespace App\Models\Administrasi;

use App\Models\Finance\PaymentAccount;
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
        'include_bank',
        'received_from',
        'payment_for',
        'remaining',
        'kwintansi_date',
        'location',
    ];

    /**
     * Konversi otomatis tipe data kolom.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'kwintansi_date' => 'date',
        'include_bank' => 'boolean',
        'amount' => 'integer',
        'remaining' => 'integer',
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
