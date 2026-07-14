<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\PaymentProof;

/**
 * Model untuk tabel sales_recaps.
 *
 * Menyimpan data rekap penjualan dengan multiple items per sales.
 * Menggunakan string primary key (id_sales_recap, format: SR-xxxxx).
 *
 * Relasi:
 * - paymentProofs() → PaymentProof (hasMany via sales_recap_id)
 *
 * Casts:
 * - items → json (array of {id_item, name_item, quantity, capital_price, selling_price, from_stock})
 * - date → date
 * - total_capital, total_selling, total_profit → integer
 */
class SalesRecap extends Model
{
    use HasFactory;

    protected $table = 'sales_recaps';
    protected $primaryKey = 'id_sales_recap';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sales_recap',
        'date',
        'name_proyek',
        'items',
        'total_capital',
        'total_selling',
        'total_profit',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'items' => 'json',
        'total_capital' => 'integer',
        'total_selling' => 'integer',
        'total_profit' => 'integer',
    ];

    /**
     * Bukti pembayaran untuk rekap penjualan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class, 'sales_recap_id', 'id_sales_recap');
    }

    /**
     * Cek apakah status sudah Lunas.
     *
     * @return bool
     */
    public function isLunas(): bool
    {
        return $this->status === 'Lunas';
    }

    /**
     * Cek apakah rekap ini masih bisa diedit (belum Lunas).
     *
     * @return bool
     */
    public function canBeEdited(): bool
    {
        return !$this->isLunas();
    }
}
