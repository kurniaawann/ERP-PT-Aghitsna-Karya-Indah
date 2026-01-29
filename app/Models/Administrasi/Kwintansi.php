<?php

namespace App\Models\Administrasi;

use App\Models\Finance\PaymentAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kwintansi extends Model
{
    use HasFactory;

    protected $table = 'kwintansi';
    protected $primaryKey = 'id_kwintansi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_kwintansi',
        'amount',
        'payment_account_id',
        'include_bank',
        'received_from',
        'payment_for',
        'remaining',
        'kwintansi_date',
        'location',
    ];

    protected $casts = [
        'kwintansi_date' => 'date',
        'include_bank' => 'boolean',
        'amount' => 'integer',
        'remaining' => 'integer',
    ];

    /**
     * Relasi ke payment account
     */
    public function paymentAccount()
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    /**
     * Generate kode kwintansi berikutnya (01/AKI/25, 02/AKI/25, dst)
     */
    public static function generateKwintansiCode()
    {
        $year = date('y'); // 25 untuk 2025
        $currentYear = date('Y');

        // Ambil kwintansi terakhir untuk tahun ini
        $lastKwintansi = self::where('id_kwintansi', 'like', "%/AKI/{$year}")
            ->orderBy('id_kwintansi', 'desc')
            ->first();

        if (!$lastKwintansi) {
            return "01/AKI/{$year}";
        }

        // Ambil nomor dari kode terakhir (contoh: 01/AKI/25 -> 01)
        $parts = explode('/', $lastKwintansi->id_kwintansi);
        $lastNumber = (int) $parts[0];
        $newNumber = $lastNumber + 1;

        // Format dengan 2 digit (01, 02, ..., 99, 100, dst)
        return str_pad($newNumber, 2, '0', STR_PAD_LEFT) . "/AKI/{$year}";
    }
}
