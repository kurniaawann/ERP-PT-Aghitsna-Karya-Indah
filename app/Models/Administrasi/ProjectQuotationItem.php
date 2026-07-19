<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk item Penawaran Proyek (Project Quotation Item).
 *
 * Model ini menyimpan data item flat (tanpa grouping) untuk setiap penawaran proyek.
 * Setiap item memiliki deskripsi, volume, satuan, harga satuan, dan total harga.
 */
class ProjectQuotationItem extends Model
{
    use HasFactory;

    protected $table = 'project_quotation_items';

    protected $fillable = [
        'quotation_number',
        'order_number',
        'description',
        'volume',
        'unit',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'total_price' => 'integer',
        'order_number' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Relasi ke penawaran induk.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quotation()
    {
        return $this->belongsTo(ProjectQuotation::class, 'quotation_number', 'quotation_number');
    }
}
