<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all expense reports for this category
     */
    public function expenseReports()
    {
        return $this->hasMany(\App\Models\Report\ExpenseReport::class, 'transaction_category_id');
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get income categories
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'INCOME');
    }

    /**
     * Scope to get expense categories
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'EXPENSE');
    }
}
