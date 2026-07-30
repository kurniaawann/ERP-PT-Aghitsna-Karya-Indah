<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Model untuk tabel "cash_out_proofs" (Bukti Kas Keluar).
 *
 * Model ini menggunakan primary key "bkk_no" (string) sebagai pengganti auto-increment ID.
 * Setiap record merepresentasikan satu bukti kas keluar dengan nomor unik BKK dan CEK.
 *
 * @property string $bkk_no Nomor Bukti Kas Keluar (primary key, format: BKK-001)
 * @property string $cek_no Nomor Cek (format: CEK-001)
 * @property string $date Tanggal bukti kas keluar
 * @property string $paid_to Nama penerima pembayaran
 * @property int $amount Jumlah nominal dalam Rupiah (tanpa desimal)
 * @property string $description Keterangan/deskripsi transaksi (opsional)
 * @property string $director Nama Direktur atau Manager (tergantung template)
 * @property string $finance_head Nama Kabag Keuangan
 * @property string $template_type Tipe template: "standard" atau "hollow"
 */
class CashOutProof extends Model
{
    use HasFactory;

    /**
     * Primary key menggunakan bkk_no (string, bukan auto-increment).
     */
    protected $primaryKey = 'bkk_no';

    /**
     * Menandakan bahwa primary key bukan auto-increment.
     */
    public $incrementing = false;

    /**
     * Tipe data primary key adalah string.
     */
    protected $keyType = 'string';

    /**
     * Kolom yang diizinkan untuk mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bkk_no',
        'cek_no',
        'date',
        'paid_to',
        'amount',
        'description',
        'director',
        'finance_head',
        'template_type',
        'created_by',
    ];

    /**
     * Konversi tipe data otomatis untuk kolom tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'amount' => 'integer',
    ];

    /**
     * Menghasilkan nomor BKK berikutnya secara otomatis.
     *
     * Format nomor: BKK-001, BKK-002, BKK-003, dst.
     * Nomor diambil dari record terakhir yang ada di database.
     *
     * @return string Nomor BKK berikutnya
     */
    public static function generateBkkNo(): string
    {
        $lastBkk = self::orderBy('bkk_no', 'desc')->first();

        if (! $lastBkk) {
            return 'BKK-001';
        }

        // Ambil nomor dari kode terakhir (contoh: BKK-001 -> 001)
        $lastNumber = (int) substr($lastBkk->bkk_no, 4);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'BKK-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menghasilkan nomor CEK berikutnya secara otomatis.
     *
     * Format nomor: CEK-001, CEK-002, CEK-003, dst.
     * Nomor diambil dari record terakhir yang ada di database.
     *
     * @return string Nomor CEK berikutnya
     */
    public static function generateCekNo(): string
    {
        $lastCek = self::orderBy('cek_no', 'desc')->first();

        if (! $lastCek) {
            return 'CEK-001';
        }

        // Ambil nomor dari kode terakhir (contoh: CEK-001 -> 001)
        $lastNumber = (int) substr($lastCek->cek_no, 4);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'CEK-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
