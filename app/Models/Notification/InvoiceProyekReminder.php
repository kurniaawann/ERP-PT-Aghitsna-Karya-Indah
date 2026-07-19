<?php

namespace App\Models\Notification;

use App\Models\Finance\InvoiceProyek;
use App\Services\Finance\InvoiceCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model untuk Reminder Jatuh Tempo Invoice Proyek.
 *
 * Menyimpan data reminder untuk setiap invoice proyek dengan status:
 * - pending: invoice belum jatuh tempo
 * - notified: invoice sudah jatuh tempo
 * - paid: invoice sudah lunas
 */
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
     * Relasi ke InvoiceProyek.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice()
    {
        return $this->belongsTo(InvoiceProyek::class, 'invoice_number', 'invoice_number');
    }

    /**
     * Scope untuk filter berdasarkan status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter berdasarkan tahun.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByYear($query, $year)
    {
        return $query->whereYear('invoice_date', $year);
    }

    /**
     * Scope untuk filter reminders yang sudah jatuh tempo.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('reminder_date', '<=', now()->toDateString());
    }

    /**
     * Scope untuk filter reminders yang akan datang.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('reminder_date', '>', now()->toDateString());
    }

    /**
     * Mendapatkan instance InvoiceCalculatorService.
     *
     * @return \App\Services\Finance\InvoiceCalculatorService
     */
    protected function getCalculator(): InvoiceCalculatorService
    {
        return app(InvoiceCalculatorService::class);
    }

    /**
     * Accessor untuk total invoice yang harus dibayar (grand total).
     *
     * @return int
     */
    public function getNetAmountAttribute(): int
    {
        if ($this->relationLoaded('invoice') && $this->invoice) {
            return $this->invoice->getNetAmount();
        }

        return $this->getCalculator()->calculateNetAmount(null, $this->total_amount);
    }

    /**
     * Accessor untuk total pembayaran yang sudah masuk.
     *
     * @return int
     */
    public function getPaidAmountAttribute(): int
    {
        if ($this->relationLoaded('invoice') && $this->invoice) {
            return $this->invoice->getTotalPaidAmount();
        }

        return 0;
    }

    /**
     * Scope untuk pencarian berdasarkan nomor invoice atau nama penerima.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($subQuery) use ($search) {
            $subQuery->where('invoice_number', 'like', "%{$search}%")
                ->orWhere('recipient', 'like', "%{$search}%");
        });
    }

    /**
     * Accessor untuk sisa pembayaran.
     *
     * @return int
     */
    public function getRemainingAmountAttribute(): int
    {
        return $this->getCalculator()->calculateRemainingAmount(
            $this->net_amount,
            0,
            0,
            $this->paid_amount
        );
    }

    /**
     * Accessor untuk status tampilan reminder.
     *
     * Menggabungkan status database dengan kalkulasi real-time.
     *
     * @return string  'paid', 'expired', atau status asli
     */
    public function getDisplayStatusAttribute(): string
    {
        $isFullyPaid = $this->getCalculator()->isFullyPaid($this->net_amount, $this->paid_amount);

        if ($this->status === 'paid' || $isFullyPaid) {
            return 'paid';
        }

        if ($this->is_overdue) {
            return 'expired';
        }

        return $this->status;
    }

    /**
     * Accessor untuk mengecek apakah reminder sudah lewat jatuh tempo.
     *
     * @return bool
     */
    public function getIsOverdueAttribute(): bool
    {
        $isFullyPaid = $this->getCalculator()->isFullyPaid($this->net_amount, $this->paid_amount);

        if ($this->status === 'paid' || $isFullyPaid) {
            return false;
        }

        $reminderDate = $this->reminder_date instanceof Carbon
            ? $this->reminder_date
            : Carbon::parse($this->reminder_date);

        return $reminderDate->isPast();
    }
}
