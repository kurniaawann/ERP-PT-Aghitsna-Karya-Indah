<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasbonDeductionLog extends Model
{
    use HasFactory;

    protected $table = 'kasbon_deduction_logs';

    protected $fillable = [
        'kasbon_code',
        'employee_id',
        'payroll_id',
        'amount_deducted',
        'amount_remaining_before',
        'amount_remaining_after',
        'period_month',
        'period_year',
        'week_number',
    ];

    protected $casts = [
        'amount_deducted' => 'integer',
        'amount_remaining_before' => 'integer',
        'amount_remaining_after' => 'integer',
        'period_month' => 'integer',
        'period_year' => 'integer',
        'week_number' => 'integer',
    ];

    public function kasbon()
    {
        return $this->belongsTo(Kasbon::class, 'kasbon_code', 'kasbon_code');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_code');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }
}
