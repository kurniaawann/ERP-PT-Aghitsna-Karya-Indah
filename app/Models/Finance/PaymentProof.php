<?php

namespace App\Models\Finance;

use App\Models\Report\SalesRecap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel payment_proofs (Bukti Pembayaran).
 *
 * Menyimpan data bukti pembayaran yang terkait dengan invoice
 * (Invoice Proyek, Invoice Alumunium, atau Invoice Barang).
 *
 * @property int         $id
 * @property string      $module_type    >Nama modul (finance)
 * @property string      $invoice_type    Tipe invoice: proyek|alumunium|barang
 * @property string      $invoice_number  Nomor atau ID invoice
 * @property string|null $sales_recap_id  ID sales recap (untuk sinkronisasi status)
 * @property int|null    $payment_stage   Tahap pembayaran (untuk invoice proyek)
 * @property int         $amount          Nominal pembayaran
 * @property string      $file_name       Nama file asli
 * @property string      $file_path       Path relatif file gambar
 * @property string      $mime_type       Tipe MIME file
 * @property int|null    $file_size       Ukuran file dalam byte
 * @property \Carbon\Carbon|null $payment_date Tanggal pembayaran (manual, default created_at)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PaymentProof extends Model
{
    use HasFactory;

    protected $table = 'payment_proofs';

    /**
     * Field yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'module_type',
        'invoice_type',
        'invoice_number',
        'sales_recap_id',
        'payment_stage',
        'amount',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'created_by',
        'payment_date',
    ];

    /**
     * Type casting untuk kolom numerik.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_stage' => 'integer',
        'amount'        => 'integer',
        'file_size'     => 'integer',
        'payment_date'  => 'date',
    ];

    /**
     * Mendapatkan URL publik untuk file bukti pembayaran.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Relasi ke model SalesRecap.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salesRecap(): BelongsTo
    {
        return $this->belongsTo(SalesRecap::class, 'sales_recap_id', 'id_sales_recap');
    }
}
