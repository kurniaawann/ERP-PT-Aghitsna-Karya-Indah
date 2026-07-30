<?php

namespace App\Models\Finance;

use App\Models\Report\SalesRecap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceBarang extends Model
{
    use HasFactory;

    protected $table = 'barang_invoices';
    protected $primaryKey = 'invoice_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'recipient',
        'regarding',
        'project_description',
        'items',
        'total_capital',
        'total_selling',
        'total_profit',
        'sales_recap_id',
        'selected_payment_accounts',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'items' => 'json',
        'selected_payment_accounts' => 'json',
        'total_capital' => 'integer',
        'total_selling' => 'integer',
        'total_profit' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'invoice_number';
    }

    public function salesRecap()
    {
        return $this->belongsTo(SalesRecap::class, 'sales_recap_id', 'id_sales_recap');
    }

    public function getNetAmount(): int
    {
        return (int) max(0, $this->total_selling ?? 0);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->salesRecap?->status === 'Lunas' ? 'Sudah Lunas' : 'Belum Lunas';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->salesRecap?->status === 'Lunas'
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';
    }
}