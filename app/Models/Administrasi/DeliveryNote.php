<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $table = 'delivery_notes';
    protected $primaryKey = 'id_delivery_note';
    public $incrementing = false;
    protected $keyType = 'string';

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
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'items' => 'array',
        'total_quantity' => 'integer',
    ];

    /**
     * Generate Delivery Note ID
     */
    public static function generateDeliveryNoteId()
    {
        $prefix = 'DN';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;
        return "{$prefix}-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
