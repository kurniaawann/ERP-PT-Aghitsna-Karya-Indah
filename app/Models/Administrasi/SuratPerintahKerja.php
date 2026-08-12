<?php

namespace App\Models\Administrasi;

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
 * {sequence}/SPK/AKI/DIV.PRODUKSI/{tahun}.
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
     * Generate nomor SPK berikutnya.
     *
     * Format: {sequence}/SPK/AKI/DIV.PRODUKSI/{tahun}
     * - sequence: nomor urut 2 digit (01, 02, ...) di-increment otomatis
     * - DIV.PRODUKSI: tetap (hardcode)
     * - tahun: tahun berjalan (misal 2026)
     *
     * Urutkan berdasarkan PANJANG string DESC lalu string DESC agar benar
     * saat melewati batas digit (orderBy string murni salah, mis. "9/..."
     * dianggap lebih besar dari "10/...").
     *
     * @return string Nomor SPK yang sudah di-generate
     */
    public static function generateNomor(): string
    {
        $year = date('Y');
        $suffix = '/SPK/AKI/DIV.PRODUKSI/' . $year;

        $last = self::where('nomor', 'like', '%' . $suffix)
            ->orderByRaw('LENGTH(nomor) DESC')
            ->orderByDesc('nomor')
            ->first();

        if ($last && preg_match('/^(\d+)\//', $last->nomor, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 1;
        }

        return str_pad($next, 2, '0', STR_PAD_LEFT) . $suffix;
    }

    /**
     * Mendapatkan nomor segmen berikutnya (bingkai digit depan).
     *
     * @return int Nomor urut berikutnya
     */
    public static function getNextSequence(): int
    {
        $year = date('Y');
        $suffix = '/SPK/AKI/DIV.PRODUKSI/' . $year;

        $last = self::where('nomor', 'like', '%' . $suffix)
            ->orderByRaw('LENGTH(nomor) DESC')
            ->orderByDesc('nomor')
            ->first();

        if ($last && preg_match('/^(\d+)\//', $last->nomor, $matches)) {
            return (int) $matches[1] + 1;
        }

        return 1;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
