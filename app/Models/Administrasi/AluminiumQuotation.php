<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\PaymentAccount;

class AluminiumQuotation extends Model
{
    use HasFactory;

    protected $table = 'aluminium_quotations';
    protected $primaryKey = 'quotation_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'quotation_number',
        'sequence_number',
        'date',
        'subject',
        'recipient',
        'recipient_address',
        'total_amount',
        'amount_in_words',
        'selected_payment_accounts',
        'signed_by',
        'division',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'integer',
        'sequence_number' => 'integer',
        'selected_payment_accounts' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'quotation_number';
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function groups()
    {
        return $this->hasMany(AluminiumQuotationGroup::class, 'quotation_number', 'quotation_number')
            ->orderBy('order_number');
    }

    public function paymentAccounts()
    {
        $ids = $this->selected_payment_accounts ?? [];
        if (empty($ids)) {
            return PaymentAccount::active()->get();
        }
        return PaymentAccount::whereIn('id', $ids)->orderBy('id')->get();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate next quotation number: {n}/{n}/ALU/{yy}
     * e.g. 1/1/ALU/26
     */
    public static function generateQuotationNumber(): string
    {
        $year = date('y');

        $last = self::where('quotation_number', 'like', "%/ALU/{$year}")
            ->orderBy('sequence_number', 'desc')
            ->first();

        $next = $last ? ($last->sequence_number + 1) : 1;

        return "{$next}/{$next}/ALU/{$year}";
    }

    public static function getNextSequenceNumber(): int
    {
        $year = date('y');
        $last = self::where('quotation_number', 'like', "%/ALU/{$year}")
            ->orderBy('sequence_number', 'desc')
            ->first();
        return $last ? ($last->sequence_number + 1) : 1;
    }
}
