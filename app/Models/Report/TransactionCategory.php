<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

/**
 * Model untuk tabel transaction_categories.
 *
 * Menyimpan data kategori transaksi (pemasukan/pengeluaran)
 * yang digunakan dalam modul laporan dan rekap pengeluaran.
 */
class TransactionCategory extends Model
{
    use HasFactory;

    /** @var string Tipe kategori pemasukan */
    const TYPE_INCOME = 'INCOME';

    /** @var string Tipe kategori pengeluaran */
    const TYPE_EXPENSE = 'EXPENSE';

    protected $fillable = [
        'name',
        'code',
        'type',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke model ExpenseRecap.
     * Satu kategori dapat memiliki banyak rekap pengeluaran.
     *
     * @return HasMany
     */
    public function expenseRecaps(): HasMany
    {
        return $this->hasMany(\App\Models\Report\ExpenseRecap::class, 'transaction_category_id');
    }

    /**
     * Relasi ke user yang membuat kategori ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: hanya kategori yang aktif.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: hanya kategori tipe pemasukan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    /**
     * Scope: hanya kategori tipe pengeluaran.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }
}
