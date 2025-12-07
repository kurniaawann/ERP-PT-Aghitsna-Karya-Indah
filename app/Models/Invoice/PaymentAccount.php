<?php

namespace App\Models\Invoice;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_name',
        'account_number',
        'account_holder',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk hanya mendapatkan rekening aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('id');
    }
}
