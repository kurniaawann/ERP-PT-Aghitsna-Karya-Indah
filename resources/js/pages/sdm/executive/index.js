/**
 * Halaman Index Data Petinggi - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Data Petinggi:
 * - Checkbox Pilih Semua / Batalkan Pilih Semua
 * - Manajemen status checkbox individu dan enable/disable tombol hapus
 * - Pengiriman form hapus massal dengan status memuat
 * - Penanganan submit form Tambah/Edit dengan pencegahan pengiriman ganda
 * - Pratinjau gambar tanda tangan sebelum diunggah
 * - Membuka gambar tanda tangan di tab baru
 *
 * Fungsi yang dipanggil dari atribut HTML inline diekspos ke window
 * karena Vite memuat JS sebagai ES module, bukan global.
 */

// ==========================================
// PRATINJAU TANDA TANGAN
// ==========================================

/**
 * Menampilkan pratinjau gambar tanda tangan yang dipilih pada input file.
 * Fungsi ini dipanggil dari atribut onchange di input file modal.
 *
 * @param {HTMLInputElement} input       Elemen input file yang berubah.
 * @param {string}           previewId   ID elemen <img> pratinjau.
 * @return {void}
 */
window.previewSignature = function (input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.classList.add('hidden');
    }
};

/**
 * Membuka gambar tanda tangan di tab baru.
 * Fungsi ini dipanggil dari tombol pratinjau di tabel.
 *
 * @param {string} url URL absolut gambar tanda tangan.
 * @return {void}
 */
window.openSignature = function (url) {
    window.open(url, '_blank');
};

/**
 * Mengatur tampilan modal edit saat checkbox "Hapus tanda tangan" dicentang.
 * Fungsi ini dipanggil dari atribut onchange di checkbox modal edit.
 *
 * Alur:
 * - Dicentang: sembunyikan gambar tanda tangan saat ini, kosongkan &
 *   nonaktifkan input file agar tidak bisa memilih gambar baru.
 * - Dibatalkan: tampilkan kembali gambar tanda tangan saat ini dan aktifkan
 *   input file kembali.
 *
 * @param {HTMLInputElement} checkbox  Elemen checkbox hapus tanda tangan.
 * @param {string|number}    id        ID petinggi (untuk prefix elemen).
 * @return {void}
 */
window.toggleRemoveSignature = function (checkbox, id) {
    const currentImage = document.getElementById('current-signature-' + id);
    const preview = document.getElementById('signature-image-preview-' + id);
    const fileInput = document.getElementById('signature-image-input-' + id);

    if (currentImage) {
        currentImage.classList.toggle('hidden', checkbox.checked);
    }

    if (preview) {
        preview.src = '';
        preview.classList.add('hidden');
    }

    if (fileInput) {
        fileInput.disabled = checkbox.checked;
        fileInput.value = '';
    }
};

// ==========================================
// CHECKBOX PILIH SEMUA
// ==========================================

/**
 * Memperbarui Status Tombol Hapus
 * Aktifkan/nonaktifkan tombol hapus berdasarkan jumlah checkbox yang dipilih.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

    if (!deleteButton) return;

    if (checkedCheckboxes.length > 0) {
        deleteButton.disabled = false;
        deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.add('hover:bg-btn-delete-hover');
    } else {
        deleteButton.disabled = true;
        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.remove('hover:bg-btn-delete-hover');
    }
}

// ==========================================
// KONFIRMASI HAPUS MASSAL
// ==========================================

/**
 * Mengirim Form Hapus
 * Menampilkan loading spinner pada tombol konfirmasi lalu mengirim form hapus.
 */
window.submitDeleteForm = function () {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
};

// ==========================================
// PENANGANAN SUBMIT FORM TAMBAH/EDIT
// ==========================================

/**
 * Menangani Submit Modal Tambah
 * Mencegah double submit pada form tambah petinggi.
 */
function initAddFormHandler() {
    const addForm = document.querySelector('#addModal form');
    if (!addForm) return;

    addForm.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn)) {
            e.preventDefault();
            return false;
        }
    });
}

/**
 * Menangani submit semua form modal Edit petinggi.
 */
function initEditFormHandlers() {
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Menginisialisasi seluruh fungsionalitas halaman petinggi saat DOM siap.
 */
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateDeleteButtonState();
        });
    }

    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    initAddFormHandler();
    initEditFormHandlers();
});
