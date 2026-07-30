<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk Item Penawaran Aluminium (Aluminium Quotation Item).
 *
 * Menyimpan detail item dalam sebuah kelompok penawaran.
 * Setiap item memiliki keterangan, volume, satuan, harga satuan, dan total harga.
 */
class AluminiumQuotationItem extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan.
     */
    protected $table = 'aluminium_quotation_items';

    /**
     * Kolom yang boleh diisi secara massal.
     */
    protected $fillable = [
        'group_id',
        'order_number',
        'description',
        'volume',
        'unit',
        'unit_price',
        'total_price',
    ];

    /**
     * Konversi tipe data kolom.
     */
    protected $casts = [
        'unit_price' => 'integer',
        'total_price' => 'integer',
        'order_number' => 'integer',
    ];

    // ─── RELATIONSHIPS ────────────────────────────────────────────────

    /**
     * Relasi: Item ini milik kelompok tertentu.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(AluminiumQuotationGroup::class, 'group_id');
    }
}
