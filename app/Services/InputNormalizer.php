<?php

namespace App\Services;

class InputNormalizer
{
    public static function normalizeCurrency($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_string($value)) {
            $value = preg_replace('/[^0-9-]/', '', $value);
        }

        return (int) $value;
    }

    public static function normalizeDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_string($value)) {
            // Jika mengandung koma, asumsikan koma adalah desimal dan titik adalah ribuan (format ID)
            if (strpos($value, ',') !== false) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // Jika tidak ada koma, tapi ada lebih dari satu titik, maka titik adalah ribuan
                if (substr_count($value, '.') > 1) {
                    $value = str_replace('.', '', $value);
                }
                // Jika hanya satu titik, kita asumsikan desimal kecuali jika itu diikuti tepat 3 digit 
                // dan ini adalah bidang yang biasanya bernilai besar. 
                // Namun untuk amannya, di sini kita biarkan titik tunggal sebagai desimal
                // karena volume/persentase biasanya pakai titik desimal jika tidak pakai koma.
            }
            $value = preg_replace('/[^0-9.\-]/', '', $value);
        }

        return (float) $value;
    }
}
