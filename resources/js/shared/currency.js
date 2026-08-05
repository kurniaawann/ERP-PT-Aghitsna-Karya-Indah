/**
 * Helper Mata Uang
 * Utilitas bersama untuk parsing dan pemformatan nilai mata uang Rupiah Indonesia.
 */

/**
 * Parse nilai input mata uang menjadi angka bulat (integer).
 *
 * Alur:
 * 1. Konversi nilai input ke string lalu trim spasi di awal/akhir.
 * 2. Jika string kosong, kembalikan 0.
 * 3. Buang semua karakter selain digit dan tanda minus menggunakan regex.
 * 4. Konversi hasil ke integer basis 10; jika bukan angka valid, kembalikan 0.
 *
 * @param  {*}  value  Nilai mentah dari input (string, number, atau null/undefined).
 * @returns {number}  Nilai integer hasil parsing; 0 bila kosong atau tidak valid.
 */
function parseCurrencyInput(value) {
    const rawValue = String(value ?? '').trim();

    if (!rawValue) {
        return 0;
    }

    return parseInt(rawValue.replace(/[^0-9-]/g, ''), 10) || 0;
}

/**
 * Format nilai input elemen (mis. <input type="text">) sebagai angka ribuan
 * dengan locale Indonesia saat mengetik (live formatting).
 *
 * Alur:
 * 1. Abaikan jika elemen input tidak ada.
 * 2. Ambil nilai input, buang semua karakter non-digit.
 * 3. Jika ada digit, format dengan Intl.NumberFormat('id-ID') sehingga muncul
 *    pemisah ribuan; jika kosong, kosongkan input.
 *
 * @param  {HTMLInputElement}  input  Elemen input yang akan diformat nilainya.
 * @returns {void}  Tidak mengembalikan nilai; mengubah properti `input.value`.
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

/**
 * Format angka menjadi teks Rupiah, mis. "Rp 1.500.000".
 *
 * Mengonversi nilai ke number (fallback 0 bila NaN) lalu memformatnya
 * dengan toLocaleString('id-ID') yang menyertakan pemisah ribuan titik.
 *
 * @param  {*}  value  Nilai angka/string yang akan diformat.
 * @returns {string}  Teks Rupiah hasil format.
 */
function formatRupiah(value) {
    return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
}

/**
 * Ekspos utilitas mata uang ke global window agar dapat dipakai dari
 * atribut onclick / inline script di Blade.
 *
 * @returns {void}
 */
window.parseCurrencyInput = parseCurrencyInput;

/**
 * Ekspos utilitas format input mata uang ke global window.
 *
 * @returns {void}
 */
window.formatCurrencyInput = formatCurrencyInput;

/**
 * Ekspos utilitas format Rupiah ke global window.
 *
 * @returns {void}
 */
window.formatRupiah = formatRupiah;
