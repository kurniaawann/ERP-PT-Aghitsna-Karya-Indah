<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk modul Tanda Terima Dokumen.
 *
 * Model ini merepresentasikan data tanda terima dokumen yang diterima
 * oleh perusahaan, termasuk informasi pengirim, perihal, bentuk dokumen,
 * tanggal/jam penerimaan, dan lokasi.
 *
 * Primary key menggunakan string (id_document) dengan format DOC-XXX.
 */
class DocumentReceipt extends Model
{
    use HasFactory;

    /**
     * Nama kolom primary key (string, bukan auto-increment).
     */
    protected $primaryKey = 'id_document';

    /**
     * Primary key bukan auto-increment (dibuat manual via generateDocumentCode).
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
        'id_document',
        'received_from',
        'regarding',
        'form_of',
        'receipt_date',
        'receipt_time',
        'location',
        'created_by',
    ];

    /**
     * Konversi otomatis tipe data kolom.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'receipt_date' => 'date',
    ];

    /**
     * Generate kode dokumen berikutnya dengan format DOC-001, DOC-002, dst.
     *
     * Metode ini mengambil kode dokumen terakhir dari database,
     * mengekstrak nomor urut, menambah 1, lalu memformat ulang
     * dengan 3 digit angka (001, 002, ..., 999, 1000, dst).
     *
     * @return string Kode dokumen baru (contoh: DOC-001)
     */
    public static function generateDocumentCode(): string
    {
        $lastDocument = self::orderBy('id_document', 'desc')->first();

        if (! $lastDocument) {
            return 'DOC-001';
        }

        // Ambil nomor dari kode terakhir (contoh: DOC-001 -> 001)
        $lastNumber = (int) substr($lastDocument->id_document, 4);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'DOC-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
