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

        // if ($number < 12) {
        //     return $words[$number];
        // } elseif ($number < 20) {
        //     return $words[$number - 10] . ' belas';
        // } elseif ($number < 100) {
        //     return $words[floor($number / 10)] . ' puluh ' . $words[$number % 10];
        // } elseif ($number < 200) {
        //     return 'seratus ' . terbilang($number - 100);
        // } elseif ($number < 1000) {
        //     return $words[floor($number / 100)] . ' ratus ' . terbilang($number % 100);
        // } elseif ($number < 2000) {
        //     return 'seribu ' . terbilang($number - 1000);
        // } elseif ($number < 1000000) {
        //     return terbilang(floor($number / 1000)) . ' ribu ' . terbilang($number % 1000);
        // } elseif ($number < 1000000000) {
        //     return terbilang(floor($number / 1000000)) . ' juta ' . terbilang($number % 1000000);
        // } elseif ($number < 1000000000000) {
        //     return terbilang(floor($number / 1000000000)) . ' miliar ' . terbilang($number % 1000000000);
        // } elseif ($number < 1000000000000000) {
        //     return terbilang(floor($number / 1000000000000)) . ' triliun ' . terbilang($number % 1000000000000);
        // }
        if ($number < 12) {
    return $words[$number];
    } elseif ($number < 20) {
        return $words[$number - 10] . ' belas';
    } elseif ($number < 100) {
        return $words[(int)floor($number / 10)] . ' puluh ' . $words[$number % 10];
    } elseif ($number < 200) {
        return 'seratus ' . terbilang($number - 100);
    } elseif ($number < 1000) {
        return $words[(int)floor($number / 100)] . ' ratus ' . terbilang($number % 100);
    } elseif ($number < 2000) {
        return 'seribu ' . terbilang($number - 1000);
    } elseif ($number < 1000000) {
        return terbilang((int)floor($number / 1000)) . ' ribu ' . terbilang($number % 1000);
    } elseif ($number < 1000000000) {
        return terbilang((int)floor($number / 1000000)) . ' juta ' . terbilang($number % 1000000);
    } elseif ($number < 1000000000000) {
        return terbilang((int)floor($number / 1000000000)) . ' miliar ' . terbilang($number % 1000000000);
    } elseif ($number < 1000000000000000) {
        return terbilang((int)floor($number / 1000000000000)) . ' triliun ' . terbilang($number % 1000000000000);
    }
        return '';
    }
}

if (!function_exists('generateExpenseRecapId')) {
    /**
     * Generate unique Expense Recap ID.
     *
     * Format: RE-00001
     * Menggunakan RecapExpenseService::generateId() yang sudah
     * menggunakan lockForUpdate() untuk mencegah race condition.
     *
     * @return string
     */
    function generateExpenseRecapId()
    {
        return app(\App\Services\Finance\RecapExpenseService::class)->generateId();
    }
}
