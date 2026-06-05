<?php

namespace App\Models\Finance;

use App\Models\Report\SalesRecap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    use HasFactory;

    protected $table = 'payment_proofs';

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
    ];

    protected $casts = [
        'payment_stage' => 'integer',
        'amount' => 'integer',
        'file_size' => 'integer',
    ];

    public function getUrlAttribute(): string
    {
        return asset($this->file_path);
    }

    public function salesRecap()
    {
        return $this->belongsTo(SalesRecap::class, 'sales_recap_id', 'id_sales_recap');
    }
}