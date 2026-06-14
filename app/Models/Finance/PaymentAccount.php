<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_number',
        'account_holder',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('id');
    }

    public function isUsed(): bool
    {
        return in_array($this->id, static::getUsedIds([$this->id]));
    }

    public static function getUsedIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $usedIds = [];

        $usedInKwintansi = DB::table('kwintansi')
            ->whereIn('payment_account_id', $ids)
            ->whereNotNull('payment_account_id')
            ->pluck('payment_account_id')
            ->toArray();

        $usedIds = array_merge($usedIds, $usedInKwintansi);

        $jsonTables = [
            'alumunium_invoices',
            'proyek_invoices',
            'notas_administrasi',
            'aluminium_quotations',
            'project_quotations',
            'rabs',
        ];

        foreach ($ids as $id) {
            if (in_array($id, $usedIds)) {
                continue;
            }

            $idInt = (int) $id;
            $jsonIdInt = json_encode($idInt);
            $jsonIdStr = json_encode((string) $idInt);

            foreach ($jsonTables as $table) {
                $exists = DB::table($table)
                    ->whereNotNull('selected_payment_accounts')
                    ->where(function ($q) use ($jsonIdInt, $jsonIdStr) {
                        $q->whereRaw('JSON_CONTAINS(selected_payment_accounts, ?)', [$jsonIdInt])
                          ->orWhereRaw('JSON_CONTAINS(selected_payment_accounts, ?)', [$jsonIdStr]);
                    })
                    ->exists();

                if ($exists) {
                    $usedIds[] = $idInt;
                    break;
                }
            }
        }

        return array_values(array_unique($usedIds));
    }
}
