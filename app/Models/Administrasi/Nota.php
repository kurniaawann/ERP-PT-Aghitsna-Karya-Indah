<?php

namespace App\Models\Administrasi;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Finance\PaymentAccount;
use App\Models\Finance\Reimburse;
use App\Models\User;

/**
 * Model untuk data Nota Administrasi.
 *
 * Tabel: notas_administrasi
 * Primary Key: id_nota (string, non-incrementing)
 *
 * Model ini menyimpan data nota penjualan/barang PT Aghitsna Karya Indah.
 * Setiap nota memiliki:
 * - Kode nota unik (NTA-001/AKI/X/26)
 * - Informasi penerima (kepada)
 * - Data faktur dan surat jalan
 * - Daftar item barang (disimpan sebagai JSON)
 * - Biaya tambahan opsional (sewa, ongkos kirim, dll)
 * - Perhitungan PPN
 * - Total keseluruhan
 *
 * Nota proyek dari Invoice Semen (Super Admin) otomatis membuat pengajuan
 * Reimbursement draft (NotaObserver); nota dari "Tambah Nota" tidak — dipilih
 * manual lewat "Ambil dari Nota" di Reimbursement. Penghapusan nota ikut
 * membersihkan draft tertaut (NotaBuilder).
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
        'invoice_number',
        'do_no',
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
     * Generate kode nota (sewa/jual) berikutnya secara otomatis.
     *
     * Format: NTA-001/AKI/X/26 (NTA = Nota, 001 = nomor urut, AKI = Aghitsna
     * Karya Indah, X = bulan romawi, 26 = tahun) — bulan & tahun diambil dari
     * tanggal nota. Nomor urut berlanjut per tahun.
     *
     * @param  \DateTimeInterface|string|null  $date  Tanggal nota (default hari ini)
     * @return string Kode nota baru (contoh: NTA-001/AKI/X/26)
     */
    public static function generateNotaCode($date = null): string
    {
        return self::generateCode('NTA', $date);
    }

    /**
     * Generate kode nota proyek berikutnya secara otomatis.
     *
     * Format: NTP-001/AKI/X/26 (NTP = Nota Proyek, 001 = nomor urut,
     * AKI = Aghitsna Karya Indah, X = bulan romawi, 26 = tahun).
     * Nomor urut terpisah dari NTA (per tipe).
     *
     * @param  \DateTimeInterface|string|null  $date  Tanggal nota (default hari ini)
     * @return string Kode nota proyek baru (contoh: NTP-001/AKI/X/26)
     */
    public static function generateProyekCode($date = null): string
    {
        return self::generateCode('NTP', $date);
    }

    /**
     * Kode nota {prefix}-{urut}/AKI/{bulan romawi}/{yy}.
     *
     * Revisi klien: setelah "AKI" dicantumkan bulan romawi. Nota lama
     * berformat {prefix}-{urut}/AKI/{yy} tetap ikut dihitung agar nomor urut
     * di tahun yang sama tidak mengulang.
     */
    private static function generateCode(string $prefix, $date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();
        $year = $date->format('y');

        $lastNumber = (int) self::where('id_nota', 'like', "{$prefix}-%/AKI/%")
            ->where('id_nota', 'like', "%/{$year}")
            ->pluck('id_nota')
            ->map(fn (string $id) => (int) substr(explode('/', $id)[0], strlen($prefix) + 1))
            ->max();

        return sprintf('%s-%03d/AKI/%s/%s', $prefix, $lastNumber + 1, bulan_romawi($date->month), $year);
    }

    /**
     * Nota proyek yang dibuat otomatis dari Invoice Semen (DO Semen) — selalu
     * menyimpan nomor invoice semen. Nota dari "Tambah Nota" tidak.
     */
    public function isFromSemenInvoice(): bool
    {
        return trim((string) $this->invoice_number) !== '';
    }

    /**
     * Apakah blok "Hormat Kami" sudah punya penandatangan (petinggi).
     */
    public function hasSigner(): bool
    {
        return trim((string) ($this->penandatangan['name'] ?? '')) !== '';
    }

    /**
     * Apakah blok "Penerima" (sewa/jual) / "Tanda Terima" (proyek) sudah
     * berisi nama penerima.
     */
    public function hasReceiver(): bool
    {
        return trim((string) $this->penerima) !== '';
    }

    /**
     * Nota sudah lengkap kedua tanda tangannya (Penerima & Hormat Kami),
     * sesuai dua blok tanda tangan di PDF. Nota yang belum lengkap wajib
     * dilengkapi dulu sebelum di-download (NotaController::ensureSigned).
     */
    public function isSigned(): bool
    {
        return $this->hasSigner() && $this->hasReceiver();
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
     * Relasi ke pengajuan reimburse yang dibuat otomatis dari nota ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function reimburse(): HasOne
    {
        return $this->hasOne(Reimburse::class, 'id_nota', 'id_nota');
    }

    /**
     * Pakai NotaBuilder agar setiap penghapusan nota (per model maupun massal)
     * ikut menghapus pengajuan reimburse draft yang tertaut.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Models\Administrasi\NotaBuilder
     */
    public function newEloquentBuilder($query): NotaBuilder
    {
        return new NotaBuilder($query);
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
