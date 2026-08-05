/**
 * Rekap Aluminium — Modular JavaScript
 *
 * Fitur:
 * - Toggle dropdown print
 * - Filter auto-submit (bulan, tahun)
 * - Reset status submit saat halaman ditampilkan
 */

// ============================================================
// HELPER BERSAMA
// ============================================================

/**
 * Menangani submit form (status loading).
 * @param {HTMLButtonElement} submitBtn
 * @param {string} originalText
 * @param {string} loadingText
 * @returns {boolean}
 */
function handleFormSubmit(submitBtn, originalText, loadingText = 'Menyimpan...') {
    if (window._isSubmitting) return false;
    window._isSubmitting = true;
    if (submitBtn) {
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    return true;
}

/**
 * Mereset status submit form.
 */
function resetFormSubmitState() {
    window._isSubmitting = false;
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
    });
}

// ============================================================
// INISIALISASI
// ============================================================

/**
 * Inisialisasi logika halaman saat DOM siap.
 *
 * Alur:
 * 1. Mengikat event 'change' pada dropdown bulan dan tahun; saat berubah,
 *    form filter langsung di-submit (auto-submit).
 * 2. Mengikat event 'pageshow' untuk mereset status submit agar tombol
 *    tidak terkunci ketika halaman dimuat ulang (tombol back browser).
 */
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // FILTER AUTO-SUBMIT
    // ============================================================

    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');
    const filterForm = monthSelect ? monthSelect.closest('form') : null;

    if (monthSelect && filterForm) {
        monthSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (yearSelect && filterForm) {
        yearSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    // ============================================================
    // RESET STATUS SUBMIT SAAT HALAMAN DITAMPILKAN
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
