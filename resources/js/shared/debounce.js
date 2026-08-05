/**
 * Debounce Utility
 *
 * Menunda eksekusi fungsi sampai jangka waktu tertentu setelah
 * pemanggilan terakhir. Berguna untuk mencegah eksekusi berlebihan
 * pada input pencarian (live search).
 *
 * @param  {Function}  fn       - Fungsi yang akan di-debounce
 * @param  {number}    delay    - Delay dalam milidetik (default: 500ms)
 * @returns {Function}  Fungsi yang sudah di-debounce
 */
/**
 * Membuat versi debounce dari sebuah fungsi.
 *
 * Alur:
 * 1. Simpan timer aktif pada closure (timer).
 * 2. Setiap kali fungsi hasil debounce dipanggil, batalkan timer sebelumnya
 *    (jika ada) agar hanya pemanggilan terakhir yang dieksekusi.
 * 3. Jadwalkan eksekusi fn setelah delay ms; ketika tiba, panggil fn dengan
 *    konteks `this` dan seluruh argumen pemanggilan terakhir, lalu reset timer.
 *
 * @param  {Function}  fn     Fungsi yang akan di-debounce.
 * @param  {number}    delay  Delay dalam milidetik sebelum fn dieksekusi (default: 500).
 * @returns {Function}  Fungsi baru yang sudah di-debounce; membungkus pemanggilan asli.
 */
function debounce(fn, delay = 500) {
    let timer = null;

    return function (...args) {
        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            fn.apply(this, args);
            timer = null;
        }, delay);
    };
}

/**
 * Ekspos utilitas debounce ke global window.
 *
 * @returns {void}
 */
window.debounce = debounce;
