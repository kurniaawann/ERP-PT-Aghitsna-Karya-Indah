<?php

if (!function_exists('terbilang')) {
    /**
     * Convert number to Indonesian words
     *
     * @param int $number
     * @return string
     */
    function terbilang($number)
    {
        $number = abs($number);
        $words = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas'
        ];

        if ($number < 12) {
            return $words[$number];
        } elseif ($number < 20) {
            return $words[$number - 10] . ' belas';
        } elseif ($number < 100) {
            return $words[floor($number / 10)] . ' puluh ' . $words[$number % 10];
        } elseif ($number < 200) {
            return 'seratus ' . terbilang($number - 100);
        } elseif ($number < 1000) {
            return $words[floor($number / 100)] . ' ratus ' . terbilang($number % 100);
        } elseif ($number < 2000) {
            return 'seribu ' . terbilang($number - 1000);
        } elseif ($number < 1000000) {
            return terbilang(floor($number / 1000)) . ' ribu ' . terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            return terbilang(floor($number / 1000000)) . ' juta ' . terbilang($number % 1000000);
        } elseif ($number < 1000000000000) {
            return terbilang(floor($number / 1000000000)) . ' miliar ' . terbilang($number % 1000000000);
        } elseif ($number < 1000000000000000) {
            return terbilang(floor($number / 1000000000000)) . ' triliun ' . terbilang($number % 1000000000000);
        }

        return '';
    }
}

if (!function_exists('generateExpenseRecapId')) {
    /**
     * Generate unique Expense Recap ID
     * Format: ER-001, ER-002, etc
     * 
     * @return string
     */
    function generateExpenseRecapId()
    {
        $lastExpenseRecap = \App\Models\Report\ExpenseRecap::where('id', 'like', 'ER-%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastExpenseRecap) {
            return 'ER-001';
        }

        $lastNumber = intval(substr($lastExpenseRecap->id, 3)); // Skip "ER-"
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return 'ER-' . $newNumber;
    }
}
