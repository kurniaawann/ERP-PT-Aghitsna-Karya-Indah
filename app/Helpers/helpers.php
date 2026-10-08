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
            $result = $words[$number];
        } elseif ($number < 20) {
            $result = $words[$number - 10] . ' belas';
        } elseif ($number < 100) {
            $result = $words[(int)floor($number / 10)] . ' puluh ' . $words[$number % 10];
        } elseif ($number < 200) {
            $result = 'seratus ' . terbilang($number - 100);
        } elseif ($number < 1000) {
            $result = $words[(int)floor($number / 100)] . ' ratus ' . terbilang($number % 100);
        } elseif ($number < 2000) {
            $result = 'seribu ' . terbilang($number - 1000);
        } elseif ($number < 1000000) {
            $result = terbilang((int)floor($number / 1000)) . ' ribu ' . terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            $result = terbilang((int)floor($number / 1000000)) . ' juta ' . terbilang($number % 1000000);
        } elseif ($number < 1000000000000) {
            $result = terbilang((int)floor($number / 1000000000)) . ' miliar ' . terbilang($number % 1000000000);
        } elseif ($number < 1000000000000000) {
            $result = terbilang((int)floor($number / 1000000000000)) . ' triliun ' . terbilang($number % 1000000000000);
        } else {
            $result = '';
        }

        // Rapikan spasi: bagian bernilai nol (mis. "puluh " + "") tidak boleh
        // meninggalkan spasi ganda/di ujung ("lima puluh  ribu " → "lima puluh ribu").
        return trim(preg_replace('/\s+/', ' ', $result));
    }
}

if (!function_exists('format_persen')) {
    /**
     * Format angka persen tanpa pembulatan ke bilangan bulat.
     *
     * Desimal ditampilkan apa adanya (maks. 2 digit) dengan koma, dan nol di
     * belakang dibuang: 2.5 → "2,5", 11 → "11", 12.25 → "12,25".
     *
     * @param  float|int|string|null  $value
     * @return string
     */
    function format_persen($value)
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
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

if (!function_exists('format_rupiah')) {
    /**
     * Format nominal Rupiah untuk dokumen cetak (PDF/Excel): "Rp. 50.000".
     *
     * Dipakai seragam di semua export (revisi klien). Nilai negatif ditulis
     * "Rp. -50.000". Tampilan layar aplikasi tetap memakai format lamanya.
     *
     * @param  float|int|string|null  $value
     * @return string
     */
    function format_rupiah($value)
    {
        return 'Rp. ' . number_format((float) $value, 0, ',', '.');
    }
}
