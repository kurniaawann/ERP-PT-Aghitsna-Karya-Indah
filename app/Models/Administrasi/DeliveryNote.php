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
        'status',
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

    /**
     * Get status label in Indonesian
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'approved' => 'Disetujui',
            'shipped' => 'Dikirim',
            'delivered' => 'Tiba',
            'cancelled' => 'Dibatalkan',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'draft' => 'bg-gray-100 text-gray-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'shipped' => 'bg-yellow-100 text-yellow-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];

        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
