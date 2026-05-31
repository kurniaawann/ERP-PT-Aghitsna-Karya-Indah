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
            $value = str_replace(',', '.', $value);
            $value = preg_replace('/[^0-9.\-]/', '', $value);
        }

        return (float) $value;
    }
}
