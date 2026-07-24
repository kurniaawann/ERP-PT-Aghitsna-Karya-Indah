<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk entitas Surat Jalan (Delivery Note).
 *
 * Menyimpan data pengiriman barang meliputi informasi
 * pengirim, penerima, daftar barang, sopir, dan kendaraan.
 *
 * @package App\Models\Administrasi
 */
class DeliveryNote extends Model
{
    use HasFactory;

    /** @var string Nama tabel database */
    protected $table = 'delivery_notes';

    /** @var string Kolom primary key */
    protected $primaryKey = 'id_delivery_note';

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
        'id_delivery_note',
        'document_number',
        'delivery_date',
        'shipper_name',
        'shipper_address',
        'receiver_name',
        'receiver_address',
        'description',
        'items',
        'driver_name',
        'vehicle_number',
        'total_quantity',
        'notes',
        'created_by',
    ];

    /**
     * Konversi tipe data kolom secara otomatis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'delivery_date' => 'date',
        'items' => 'array',
        'total_quantity' => 'integer',
    ];

    /**
     * Generate ID unik untuk surat jalan baru.
     *
     * Format: DN-YYYYMMDD-XXXX
     * - DN: prefix Delivery Note
     * - YYYYMMDD: tanggal pembuatan
     * - XXXX: nomor urut 4 digit (0001, 0002, dst.)
     *
     * @return string ID surat jalan yang dihasilkan
     */
    public static function generateDeliveryNoteId()
    {
        $prefix = 'DN';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;
        return "{$prefix}-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
