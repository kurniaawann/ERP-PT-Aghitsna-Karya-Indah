<?php

namespace App\Models\Administrasi;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Model untuk entitas Surat Perintah Kerja (SPK).
 *
 * Menyimpan data surat perintah kerja meliputi informasi proyek, lokasi,
 * tanggal, Pemberi Tugas (nama & alamat), yang bertanda tangan di bawah ini
 * (nama & jabatan), serta daftar item pekerjaan (keterangan, volume, satuan,
 * harga, jumlah).
 *
 * Primary key: nomor (string, bukan auto-increment) dengan format
 * {sequence}/SPK/AKI/{divisi}/{bulan romawi}/{tahun}, mis. 01/SPK/AKI/DIV.PRODUKSI/VIII/2026.
 *
 * @package App\Models\Administrasi
 */
class SuratPerintahKerja extends Model
{
    use HasFactory;

    /** @var string Nama tabel database */
    protected $table = 'surat_perintah_kerjas';

    /** @var string Kolom primary key */
    protected $primaryKey = 'nomor';

    /** @var bool Primary key bukan auto-increment */
    public $incrementing = false;

    /** @var string Tipe data primary key */
    protected $keyType = 'string';

    /**
     * Kolom yang boleh diisi secara massal (mass assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor',
        'proyek',
        'lokasi',
        'tanggal',
        'pemberi_tugas_nama',
        'pemberi_tugas_alamat',
        'signer_nama',
        'signer_jabatan',
        'items',
        'total_amount',
        'created_by',
    ];

    /**
     * Konversi tipe data kolom secara otomatis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'date',
        'items' => 'array',
        'total_amount' => 'integer',
    ];

    /**
     * Divisi default pada nomor SPK.
     */
    public const DEFAULT_DIVISI = 'DIV.PRODUKSI';

    /**
     * Generate nomor SPK berikutnya.
     *
     * Format: {sequence}/SPK/AKI/{divisi}/{bulan romawi}/{tahun}
     * - sequence: nomor urut 2 digit (01, 02, ...) per tahun, berlanjut untuk
     *   semua divisi (nomor format lama tanpa bulan romawi ikut dihitung)
     * - divisi: diisi user (default DIV.PRODUKSI) — revisi klien: bisa diganti
     * - bulan romawi & tahun: dari tanggal SPK
     *
     * @param  string|null  $divisi  Divisi (default DIV.PRODUKSI)
     * @param  \DateTimeInterface|string|null  $tanggal  Tanggal SPK (default hari ini)
     * @return string Nomor SPK yang sudah di-generate
     */
    public static function generateNomor(?string $divisi = null, $tanggal = null): string
    {
        $tanggal = $tanggal ? Carbon::parse($tanggal) : now();

        return self::buildNomor(self::getNextSequence($tanggal->year), $divisi, $tanggal);
    }

    /**
     * Mendapatkan nomor urut berikutnya untuk tahun tertentu.
     *
     * Nomor urut diambil dari angka di depan nomor (bukan urutan string,
     * yang salah saat melewati batas digit atau panjang divisi berbeda).
     *
     * @param  int|null  $year  Tahun (default tahun berjalan)
     * @return int Nomor urut berikutnya
     */
    public static function getNextSequence(?int $year = null): int
    {
        $year = $year ?: (int) date('Y');

        $last = self::where('nomor', 'like', '%/SPK/AKI/%')
            ->where('nomor', 'like', '%/' . $year)
            ->pluck('nomor')
            ->map(fn (string $nomor) => (int) explode('/', $nomor)[0])
            ->max();

        return (int) $last + 1;
    }

    /**
     * Menyusun nomor SPK: {sequence}/SPK/AKI/{divisi}/{bulan romawi}/{tahun}.
     *
     * @param  int  $sequence  Nomor urut
     * @param  string|null  $divisi  Divisi (kosong = DIV.PRODUKSI)
     * @param  \DateTimeInterface|string  $tanggal  Tanggal SPK (bulan romawi)
     * @param  int|null  $year  Tahun nomor (default tahun dari tanggal)
     * @return string
     */
    public static function buildNomor(int $sequence, ?string $divisi, $tanggal, ?int $year = null): string
    {
        $tanggal = Carbon::parse($tanggal);

        return sprintf(
            '%s/SPK/AKI/%s/%s/%d',
            str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            self::normalizeDivisi($divisi),
            bulan_romawi($tanggal->month),
            $year ?: $tanggal->year,
        );
    }

    /**
     * Divisi pada nomor ditulis kapital tanpa spasi berlebih; kosong = default.
     * Garis miring diganti "-" agar segmen nomor tidak bergeser.
     */
    public static function normalizeDivisi(?string $divisi): string
    {
        $divisi = str_replace('/', '-', (string) $divisi);
        $divisi = mb_strtoupper(preg_replace('/\s+/', ' ', trim($divisi)));

        return $divisi !== '' ? $divisi : self::DEFAULT_DIVISI;
    }

    /**
     * Divisi yang tercantum pada nomor SPK (segmen ke-4).
     */
    public function getDivisiAttribute(): string
    {
        return explode('/', (string) $this->nomor)[3] ?? self::DEFAULT_DIVISI;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
