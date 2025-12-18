<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashOutProof extends Model
{
    use HasFactory;

    protected $primaryKey = 'bkk_no';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'bkk_no',
        'cek_no',
        'date',
        'paid_to',
        'amount',
        'description',
        'director',
        'finance_head',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'integer',
    ];

    /**
     * Generate nomor BKK berikutnya (BKK-001, BKK-002, dst)
     */
    public static function generateBkkNo()
    {
        $lastBkk = self::orderBy('bkk_no', 'desc')->first();

        if (!$lastBkk) {
            return 'BKK-001';
        }

        // Ambil nomor dari kode terakhir (contoh: BKK-001 -> 001)
        $lastNumber = (int) substr($lastBkk->bkk_no, 4);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'BKK-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate nomor CEK berikutnya (CEK-001, CEK-002, dst)
     */
    public static function generateCekNo()
    {
        $lastCek = self::orderBy('cek_no', 'desc')->first();

        if (!$lastCek) {
            return 'CEK-001';
        }

        // Ambil nomor dari kode terakhir (contoh: CEK-001 -> 001)
        $lastNumber = (int) substr($lastCek->cek_no, 4);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'CEK-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}
