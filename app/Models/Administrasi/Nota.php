<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Finance\PaymentAccount;
use App\Models\User;

/**
 * Model untuk data Nota Administrasi.
 *
 * Tabel: notas_administrasi
 * Primary Key: id_nota (string, non-incrementing)
 *
 * Model ini menyimpan data nota penjualan/barang PT Aghitsna Karya Indah.
 * Setiap nota memiliki:
 * - Kode nota unik (NTA-001/AKI/26)
 * - Informasi penerima (kepada)
 * - Data faktur dan surat jalan
 * - Daftar item barang (disimpan sebagai JSON)
 * - Biaya tambahan opsional (sewa, ongkos kirim, dll)
 * - Perhitungan PPN
 * - Total keseluruhan
 */
class Nota extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'notas_administrasi';

    /**
     * Primary key menggunakan id_nota (string, non-incrementing).
     */
    protected $primaryKey = 'id_nota';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Konstanta tipe nota.
     */
    public const TIPE_SEWA_JUAL = 'sewa_jual';
    public const TIPE_PROYEK = 'proyek';

    /**
     * Kolom yang boleh diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'id_nota',
        'tipe_nota',
        'nama_proyek',
        'location',
        'nota_date',
        'periode_start',
        'periode_end',
        'kepada',
        'faktur_no',
        'sj_no',
        'items',
        'penerima',
        'penandatangan',
        'sewa_jual',
        'ongkos_kirim',
        'bongkar_pasang',
        'lembur',
        'uang_jaminan',
        'jumlah_total',
        'selected_payment_accounts',
        'ppn_percentage',
        'ppn_amount',
        'total_with_ppn',
        'created_by',
    ];

    /**
     * Casting kolom ke tipe data PHP yang sesuai.
     *
     * - items: array (JSON)
     * - selected_payment_accounts: array (JSON)
     * - nota_date: date (Carbon)
     * - ppn_percentage: decimal dengan 2 angka desimal
     * - Sisanya: integer
     */
    protected $casts = [
        'nota_date' => 'date',
        'periode_start' => 'date',
        'periode_end' => 'date',
        'items' => 'array',
        'penerima' => 'string',
        'penandatangan' => 'array',
        'sewa_jual' => 'integer',
        'ongkos_kirim' => 'integer',
        'bongkar_pasang' => 'integer',
        'lembur' => 'integer',
        'uang_jaminan' => 'integer',
        'jumlah_total' => 'integer',
        'selected_payment_accounts' => 'array',
        'ppn_percentage' => 'decimal:2',
        'ppn_amount' => 'integer',
        'total_with_ppn' => 'integer',
    ];

    /**
     * Generate kode nota berikutnya secara otomatis.
     *
     * Format: NTA-001/AKI/26 (NTA = Nota, 001 = nomor urut, AKI = Aghitsna Karya Indah, 26 = tahun)
     * Nomor urut akan otomatis increment berdasarkan nota terakhir di tahun yang sama.
     *
     * @return string Kode nota baru (contoh: NTA-001/AKI/26)
     */
    public static function generateNotaCode(): string
    {
        $year = date('y');

        // Ambil nota terakhir dengan pola NTA-xxx/AKI/[year]
        $lastNota = self::where('id_nota', 'like', "NTA-%/AKI/{$year}")
            ->orderBy('id_nota', 'desc')
            ->first();

        if ($lastNota) {
            // Extract nomor urut dari id_nota (NTA-001/AKI/26 -> 001)
            $parts = explode('/', $lastNota->id_nota);
            $numberPart = explode('-', $parts[0])[1];
            $nextNumber = intval($numberPart) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('NTA-%03d/AKI/%s', $nextNumber, $year);
    }

    /**
     * Generate kode nota proyek berikutnya secara otomatis.
     *
     * Format: NTP-001/AKI/26 (NTP = Nota Proyek, 001 = nomor urut,
     * AKI = Aghitsna Karya Indah, 26 = tahun).
     * Nomor urut terpisah dari NTA (per tipe).
     *
     * @return string Kode nota proyek baru (contoh: NTP-001/AKI/26)
     */
    public static function generateProyekCode(): string
    {
        $year = date('y');

        // Ambil nota terakhir dengan pola NTP-xxx/AKI/[year]
        $lastNota = self::where('tipe_nota', self::TIPE_PROYEK)
            ->where('id_nota', 'like', "NTP-%/AKI/{$year}")
            ->orderBy('id_nota', 'desc')
            ->first();

        if ($lastNota) {
            // Extract nomor urut dari id_nota (NTP-001/AKI/26 -> 001)
            $parts = explode('/', $lastNota->id_nota);
            $numberPart = explode('-', $parts[0])[1];
            $nextNumber = intval($numberPart) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('NTP-%03d/AKI/%s', $nextNumber, $year);
    }

    /**
     * Menghitung total dari seluruh items.
     *
     * @return int Total jumlah seluruh items
     */
    public function calculateItemsTotal(): int
    {
        $total = 0;
        if (is_array($this->items)) {
            foreach ($this->items as $item) {
                $total += $item['jumlah'] ?? 0;
            }
        }
        return $total;
    }

    /**
     * Menghitung grand total (total items + biaya tambahan opsional).
     *
     * @return int Grand total nota
     */
    public function calculateGrandTotal(): int
    {
        $itemsTotal = $this->calculateItemsTotal();
        $optionalTotal = ($this->sewa_jual ?? 0)
            + ($this->ongkos_kirim ?? 0)
            + ($this->bongkar_pasang ?? 0)
            + ($this->lembur ?? 0)
            + ($this->uang_jaminan ?? 0);

        return $itemsTotal + $optionalTotal;
    }

    /**
     * Relasi ke data rekening bank yang dipilih.
     *
     * Menggunakan data selected_payment_accounts (JSON array) untuk
     * mengambil data payment_accounts yang sesuai.
     *
     * @return \Illuminate\Database\Eloquent\Collection Koleksi PaymentAccount
     */
    public function paymentAccounts()
    {
        if (!$this->selected_payment_accounts) {
            return collect();
        }

        return PaymentAccount::whereIn('id', $this->selected_payment_accounts)->get();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Menghitung jumlah PPN.
     *
     * @return int Jumlah PPN
     */
    public function calculatePpn(): int
    {
        $ppnPercentage = $this->ppn_percentage ?? 12;
        return (int) ($this->jumlah_total * ($ppnPercentage / 100));
    }

    /**
     * Menghitung total setelah PPN.
     *
     * @return int Total dengan PPN
     */
    public function calculateTotalWithPpn(): int
    {
        return $this->jumlah_total + $this->calculatePpn();
    }
}
