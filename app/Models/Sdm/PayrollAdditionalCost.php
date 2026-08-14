<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk tabel payroll_additional_costs.
 *
 * Menyimpan daftar biaya lain-lain per proyek pada satu periode payroll
 * mingguan. Setiap record mewakili satu proyek + periode; rincian biaya
 * disimpan sebagai array items (masing-masing berisi nama & jumlah).
 *
 * @property int $id
 * @property string $period_start_date
 * @property string $period_end_date
 * @property string|null $project_name
 * @property array<int, array{name: string, amount: int}>|null $items
 * @property int $total_amount
 * @property string|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PayrollAdditionalCost extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'period_start_date',
        'period_end_date',
        'project_name',
        'items',
        'total_amount',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'items' => 'array',
        'total_amount' => 'integer',
    ];
}
