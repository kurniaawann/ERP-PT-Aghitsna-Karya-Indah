<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

/**
 * Model untuk Rekening Pembayaran.
 *
 * Menyimpan data rekening bank yang digunakan untuk pembayaran
 * pada berbagai modul (invoice, kwitansi, quotation, RAB).
 */
class PaymentAccount extends Model
{
    use HasFactory;

    /**
     * Kolom yang boleh diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bank_name',
        'account_number',
        'account_holder',
        'is_active',
        'created_by',
    ];

    /**
     * Konversi tipe data kolom.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope: hanya mendapatkan rekening aktif, diurutkan berdasarkan ID.
     *
     * @param  Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('id');
    }

    /**
     * Scope: filter berdasarkan kata kunci pencarian (bank, nomor, pemilik).
     *
     * @param  Builder     $query
     * @param  string|null $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('bank_name', 'like', "%{$search}%")
                ->orWhere('account_number', 'like', "%{$search}%")
                ->orWhere('account_holder', 'like', "%{$search}%");
        });
    }

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * Relasi ke user yang membuat rekening ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
