<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk Kelompok Item Penawaran Aluminium (Aluminium Quotation Group).
 *
 * Setiap penawaran memiliki beberapa kelompok item.
 * Setiap kelompok memiliki nama, urutan, dan subtotal.
 * Item-item detail disimpan di tabel terpisah (AluminiumQuotationItem).
 */
class AluminiumQuotationGroup extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan.
     */
    protected $table = 'aluminium_quotation_groups';

    /**
     * Kolom yang boleh diisi secara massal.
     */
    protected $fillable = [
        'quotation_number',
        'order_number',
        'name',
        'subtotal',
    ];

    /**
     * Konversi tipe data kolom.
     */
    protected $casts = [
        'subtotal' => 'integer',
        'order_number' => 'integer',
    ];

    // ─── RELATIONSHIPS ────────────────────────────────────────────────

    /**
     * Relasi: Kelompok ini milik penawaran tertentu.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quotation()
    {
        return $this->belongsTo(AluminiumQuotation::class, 'quotation_number', 'quotation_number');
    }

    /**
     * Relasi: Kelompok ini memiliki banyak item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(AluminiumQuotationItem::class, 'group_id')->orderBy('order_number');
    }
}
