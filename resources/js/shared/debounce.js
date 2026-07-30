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

window.debounce = debounce;
