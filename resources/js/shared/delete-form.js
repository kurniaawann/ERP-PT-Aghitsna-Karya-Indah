/**
 * Shared Delete Form Helper
 *
 * Submit form hapus dengan loading indicator pada tombol konfirmasi.
 * Dipanggil dari onclick pada modal konfirmasi hapus massal.
 */

/**
 * Submit form hapus dengan indikator loading pada tombol konfirmasi.
 *
 * Alur:
 * 1. Ambil tombol konfirmasi berdasarkan buttonId (jika ada).
 * 2. Tampilkan spinner + teks loading pada tombol, nonaktifkan tombol, dan
 *    beri kelas opacity-70/cursor-not-allowed agar tidak bisa diklik dua kali.
 * 3. Ambil elemen form berdasarkan formId lalu submit (submit asli, non-AJAX).
 * 4. Kembalikan true jika form ditemukan & disubmit, false jika tidak.
 *
 * @param  {string}  [buttonId]    ID tombol konfirmasi pada modal (default: 'confirm-btn-deleteModal').
 * @param  {string}  [formId]      ID form yang akan disubmit (default: 'deleteForm').
 * @param  {string}  [loadingText] Teks yang tampil saat proses berjalan (default: 'Menghapus...').
 * @returns {boolean}  true bila form berhasil disubmit, false bila form tidak ditemukan.
 */
function submitDeleteForm(buttonId = 'confirm-btn-deleteModal', formId = 'deleteForm', loadingText = 'Menghapus...') {
    const deleteBtn = document.getElementById(buttonId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + loadingText;
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById(formId);
    if (form) {
        form.submit();
        return true;
    }

    return false;
}

/**
 * Ekspos submitDeleteForm ke global window untuk dipakai dari atribut
 * onclick pada tombol konfirmasi modal hapus massal di Blade.
 *
 * @returns {void}
 */
window.submitDeleteForm = submitDeleteForm;
