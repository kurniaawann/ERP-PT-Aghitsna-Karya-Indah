<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\PaymentAccount;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas_administrasi';
    protected $primaryKey = 'id_nota';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_nota',
        'location',
        'nota_date',
        'kepada',
        'faktur_no',
        'sj_no',
        'items',
        'penerima',
        'sewa_jual',
        'ongkos_kirim',
        'bongkar_pasang',
        'lembur',
        'uang_jaminan',
        'jumlah_total',
        'selected_payment_accounts',
        'ppn_percentage',
        'ppn_amount',
        'total_with_ppn',
    ];

    protected $casts = [
        'nota_date' => 'date',
        'items' => 'array',
        'sewa_jual' => 'integer',
        'ongkos_kirim' => 'integer',
        'bongkar_pasang' => 'integer',
        'lembur' => 'integer',
        'uang_jaminan' => 'integer',
        'jumlah_total' => 'integer',
        'selected_payment_accounts' => 'array',
        'ppn_percentage' => 'decimal:2',
        'ppn_amount' => 'integer',
        'total_with_ppn' => 'integer',
    ];

    /**
     * Generate kode nota berikutnya (NTA-001/AKI/26, NTA-002/AKI/26, dst)
     */
    public static function generateNotaCode()
    {
        $year = date('y'); // 26 untuk 2026

        // Ambil nota terakhir dengan pola NTA-xxx/AKI/[year]
        $lastNota = self::where('id_nota', 'like', "NTA-%/AKI/{$year}")
            ->orderBy('id_nota', 'desc')
            ->first();

        if ($lastNota) {
            // Extract nomor urut dari id_nota (NTA-001/AKI/26 -> 001)
            $parts = explode('/', $lastNota->id_nota);
            $numberPart = explode('-', $parts[0])[1]; // Ambil bagian setelah "NTA-"
            $nextNumber = intval($numberPart) + 1;
        } else {
            // Jika belum ada nota tahun ini, mulai dari 1
            $nextNumber = 1;
        }

        // Format: NTA-001/AKI/26
        return sprintf('NTA-%03d/AKI/%s', $nextNumber, $year);
    }

    /**
     * Calculate total dari items
     */
    public function calculateItemsTotal()
    {
        $total = 0;
        if (is_array($this->items)) {
            foreach ($this->items as $item) {
                $total += $item['jumlah'] ?? 0;
            }
        }
        return $total;
    }

    /**
     * Calculate grand total (items + optional fees)
     */
    public function calculateGrandTotal()
    {
        $itemsTotal = $this->calculateItemsTotal();
        $optionalTotal = ($this->sewa_jual ?? 0)
            + ($this->ongkos_kirim ?? 0)
            + ($this->bongkar_pasang ?? 0)
            + ($this->lembur ?? 0)
            + ($this->uang_jaminan ?? 0);

        return $itemsTotal + $optionalTotal;
    }

    /**
     * Get selected payment accounts relation
     */
    public function paymentAccounts()
    {
        if (!$this->selected_payment_accounts) {
            return collect();
        }

        return PaymentAccount::whereIn('id', $this->selected_payment_accounts)->get();
    }

    /**
     * Calculate PPN amount
     */
    public function calculatePpn()
    {
        $ppnPercentage = $this->ppn_percentage ?? 12;
        return (int) ($this->jumlah_total * ($ppnPercentage / 100));
    }

    /**
     * Calculate total with PPN
     */
    public function calculateTotalWithPpn()
    {
        return $this->jumlah_total + $this->calculatePpn();
    }
}
