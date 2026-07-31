<?php

namespace App\Models\Sdm;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model pengeluaran tambahan / operasional proyek.
 *
 * Menyimpan biaya operasional proyek (air minum, material tambahan, dll)
 * yang dicatat saat generate payroll. Satu record mewakili satu periode
 * payroll (period_start_date - period_end_date), bukan per karyawan.
 *
 * @property int         $id
 * @property string      $period_start_date   Tanggal awal periode (YYYY-MM-DD)
 * @property string      $period_end_date     Tanggal akhir periode (YYYY-MM-DD)
 * @property string|null $project_name        Nama proyek (opsional)
 * @property array|null  $expense_items       JSON list [{name, amount}]
 * @property int         $total_amount        Total seluruh item
 * @property string|null $notes               Catatan tambahan
 * @property string|null $created_by          User id pembuat
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProjectOperationalExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_start_date',
        'period_end_date',
        'project_name',
        'expense_items',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'expense_items' => 'array',
        'total_amount' => 'integer',
    ];

    /**
     * User yang membuat record ini.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Format periode (misal: "1 - 4 Feb 2026").
     *
     * @return string
     */
    public function getFormattedPeriodAttribute(): string
    {
        $start = Carbon::parse($this->period_start_date);
        $end = Carbon::parse($this->period_end_date);

        if ($start->month === $end->month && $start->year === $end->year) {
            return $start->format('d') . ' - ' . $end->format('d M Y');
        }

        if ($start->year === $end->year) {
            return $start->format('d M') . ' - ' . $end->format('d M Y');
        }

        return $start->format('d M Y') . ' - ' . $end->format('d M Y');
    }
}
