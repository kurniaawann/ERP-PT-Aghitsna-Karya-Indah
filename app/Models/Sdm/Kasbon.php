<?php

namespace App\Models\Sdm;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for the kasbons table.
 *
 * Stores cash advance (kasbon) data for employees.
 * Each kasbon is either personal (per employee) or team (per division).
 *
 * Uses kasbon_code (string) as the primary key instead of auto-incrementing ID.
 * Codes follow the format: KSB001, KSB002, etc.
 *
 * Status flow: pending -> deducted (when payroll is generated)
 * Payment status flow: unpaid -> partial -> paid
 *
 * @property string $kasbon_code
 * @property string|null $employee_id
 * @property string $kasbon_type  personal|team
 * @property string|null $division
 * @property array<int, string>|null $project_names  Nama proyek (satu atau banyak)
 * @property int $amount
 * @property \Carbon\Carbon $kasbon_date
 * @property int|null $week_number
 * @property int $period_month
 * @property int $period_year
 * @property \Carbon\Carbon $period_start_date
 * @property \Carbon\Carbon $period_end_date
 * @property string $status  pending|deducted
 * @property int|null $deducted_in_payroll_id
 * @property int $paid_amount
 * @property int $remaining_amount
 * @property string $payment_status  unpaid|partial|paid
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read string $formatted_amount
 * @property-read string $status_label
 * @property-read string $kasbon_type_label
 * @property-read string $payment_status_label
 * @property-read int $progress_percentage
 * @property-read string $formatted_paid_amount
 * @property-read string $formatted_remaining_amount
 * @property-read \App\Models\Sdm\Employee|null $employee
 * @property-read \App\Models\Sdm\Payroll|null $payroll
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sdm\KasbonPayment[] $payments
 */
class Kasbon extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'kasbon_code';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kasbon_code',
        'employee_id',
        'kasbon_type',
        'division',
        'project_names',
        'amount',
        'kasbon_date',
        'week_number',
        'period_month',
        'period_year',
        'period_start_date',
        'period_end_date',
        'status',
        'deducted_in_payroll_id',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'remaining_amount' => 'integer',
        'week_number' => 'integer',
        'period_month' => 'integer',
        'period_year' => 'integer',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'kasbon_date' => 'date',
        'project_names' => 'array',
    ];

    // ─── Code Generation ────────────────────────────────────────────────

    /**
     * Generate the next sequential kasbon code.
     *
     * Format: KSB + 3-digit zero-padded number (KSB001, KSB002, ..., KSB999, KSB1000).
     *
     * @return string  The next available kasbon code
     */
    public static function generateKasbonCode(): string
    {
        $lastKasbon = self::orderBy('kasbon_code', 'desc')->first();

        if (!$lastKasbon) {
            return 'KSB001';
        }

        $lastNumber = (int) substr($lastKasbon->kasbon_code, 3);
        $newNumber = $lastNumber + 1;

        return 'KSB' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // ─── Relationships ──────────────────────────────────────────────────

    /**
     * Get the employee who received this kasbon.
     *
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the payroll record that deducted this kasbon.
     *
     * @return BelongsTo
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'deducted_in_payroll_id');
    }

    /**
     * Get all payment records for this kasbon.
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(KasbonPayment::class, 'kasbon_code');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────

    /**
     * Filter kasbons that have not been deducted (pending status).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Filter kasbons that have been deducted (deducted status).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeducted($query)
    {
        return $query->where('status', 'deducted');
    }

    /**
     * Filter kasbons by period month and year using period_start_date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $month  Month number (1-12)
     * @param  int  $year   Year number
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->whereMonth('period_start_date', $month)
            ->whereYear('period_start_date', $year);
    }

    /**
     * Filter kasbons by exact period_start_date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Carbon\Carbon|string  $startDate  The period start date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStartDate($query, $startDate)
    {
        return $query->where('period_start_date', $startDate instanceof Carbon
            ? $startDate->format('Y-m-d')
            : $startDate);
    }

    /**
     * Filter personal kasbons (per employee).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePersonal($query)
    {
        return $query->where('kasbon_type', 'personal');
    }

    /**
     * Filter team kasbons (per division).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTeam($query)
    {
        return $query->where('kasbon_type', 'team');
    }

    /**
     * Filter kasbons that are not fully paid.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotPaid($query)
    {
        return $query->where('payment_status', '!=', 'paid');
    }

    // ─── Aggregate Queries ──────────────────────────────────────────────

    /**
     * Get total pending kasbon amount for a specific employee in a period.
     *
     * @param  string  $employeeCode    Employee code
     * @param  \Carbon\Carbon|string  $periodStartDate  Period start date
     * @return int  Total pending kasbon amount
     */
    public static function getTotalForEmployee(string $employeeCode, $periodStartDate): int
    {
        $startDate = $periodStartDate instanceof Carbon
            ? $periodStartDate->format('Y-m-d')
            : $periodStartDate;

        return self::where('employee_id', $employeeCode)
            ->where('period_start_date', $startDate)
            ->pending()
            ->notPaid()
            ->sum('remaining_amount');
    }

    /**
     * Get total pending team kasbon amount for a specific period.
     *
     * @param  \Carbon\Carbon|string  $periodStartDate  Period start date
     * @return int  Total pending team kasbon amount
     */
    public static function getTotalTeamKasbon($periodStartDate): int
    {
        $startDate = $periodStartDate instanceof Carbon
            ? $periodStartDate->format('Y-m-d')
            : $periodStartDate;

        return self::where('kasbon_type', 'team')
            ->where('period_start_date', $startDate)
            ->pending()
            ->notPaid()
            ->sum('remaining_amount');
    }

    // ─── State Management ───────────────────────────────────────────────

    /**
     * Mark this kasbon as deducted and link it to a payroll record.
     *
     * @param  int  $payrollId  The payroll record ID
     * @return bool
     */
    public function markAsDeducted(int $payrollId): bool
    {
        $this->status = 'deducted';
        $this->deducted_in_payroll_id = $payrollId;

        return $this->save();
    }

    // ─── Accessors ──────────────────────────────────────────────────────

    /**
     * Get the formatted amount in Indonesian Rupiah format.
     *
     * @return string  Formatted amount (e.g., "Rp 150.000")
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get the human-readable status label.
     *
     * @return string  "Belum Dipotong" or "Sudah Dipotong"
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'pending' ? 'Belum Dipotong' : 'Sudah Dipotong';
    }

    /**
     * Get the human-readable kasbon type label.
     *
     * @return string  "Per Orang" or "Per Tim"
     */
    public function getKasbonTypeLabelAttribute(): string
    {
        return $this->kasbon_type === 'personal' ? 'Per Orang' : 'Per Tim';
    }

    /**
     * Get the human-readable payment status label.
     *
     * @return string  "Belum Dibayar", "Cicilan Berjalan", or "Lunas"
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Belum Dibayar',
            'partial' => 'Cicilan Berjalan',
            'paid' => 'Lunas',
        };
    }

    /**
     * Get the payment progress percentage.
     *
     * @return int  0-100
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return (int) round(($this->paid_amount / $this->amount) * 100);
    }

    /**
     * Get the formatted paid amount in Indonesian Rupiah.
     *
     * @return string  e.g., "Rp 150.000"
     */
    public function getFormattedPaidAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->paid_amount ?? 0, 0, ',', '.');
    }

    /**
     * Get the formatted remaining amount in Indonesian Rupiah.
     *
     * @return string  e.g., "Rp 350.000"
     */
    public function getFormattedRemainingAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->remaining_amount ?? 0, 0, ',', '.');
    }
}
