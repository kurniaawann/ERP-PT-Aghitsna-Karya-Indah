<?php

namespace App\Models\Notification;

use App\Models\Finance\InvoiceProyek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceProyekReminder extends Model
{
    use HasFactory;

    protected $table = 'invoice_proyek_reminders';

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'recipient',
        'total_amount',
        'reminder_date',
        'status',
        'notification_sent_at',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'invoice_date' => 'date',
        'reminder_date' => 'date',
        'notification_sent_at' => 'datetime',
    ];

    /**
     * Relasi ke InvoiceProyek
     */
    public function invoice()
    {
        return $this->belongsTo(InvoiceProyek::class, 'invoice_number', 'invoice_number');
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeByYear($query, $year)
    {
        return $query->whereYear('invoice_date', $year);
    }

    /**
     * Scope untuk filter reminders yang sudah jatuh tempo
     */
    public function scopeOverdue($query)
    {
        return $query->where('reminder_date', '<=', now()->toDateString());
    }

    /**
     * Scope untuk filter reminders yang akan datang
     */
    public function scopeUpcoming($query)
    {
        return $query->where('reminder_date', '>', now()->toDateString());
    }
}
