/**
 * Kwintansi Administrasi - JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Format input currency Rupiah (amount, remaining)
 * - Submit form hapus massal dengan loading indicator
 * - Select all checkbox dan sinkronisasi checkbox individual
 * - Pengelolaan status tombol aksi (hapus, cetak terpilih)
 * - Loading indicator pada form submit (Tambah / Edit)
 * - Cetak terpilih via AJAX download PDF
 * - Reset state saat modal backdrop diklik
 */

/* ==========================================
 * FORMAT RUPIAH
 * ========================================== */

/**
 * Mengubah input angka menjadi format Rupiah dengan pemisah ribuan.
 * Contoh: 10000 -> "10.000"
 *
 * Alur:
 * 1. Ambil nilai input dan buang seluruh karakter non-angka.
 * 2. Format ulang dengan Intl.NumberFormat('id-ID') (pemisah ribuan).
 * 3. Nilai bersih tanpa format inilah yang dikirim ke server, lalu
 *    dinormalisasi kembali oleh `InputNormalizer::normalizeCurrency()`
 *    pada `KwintansiService::create()/update()`.
 *
 * @param {HTMLInputElement} input - Element input yang akan diformat
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

// Expose ke global scope agar bisa dipanggil dari inline oninput di Blade
window.formatCurrencyInput = formatCurrencyInput;

/* ==========================================
 * PEMBANTU: Submit Form Hapus
 * ========================================== */

/**
 * Fungsi untuk submit form hapus dengan loading indicator.
 * Dipanggil dari onclick pada modal konfirmasi hapus.
 *
 * @param {string} buttonId - ID tombol konfirmasi (default: confirm-btn-deleteModal)
 * @param {string} formId - ID form yang akan di-submit (default: deleteForm)
 * @param {string} loadingText - Teks loading saat proses (default: Menghapus...)
 */
window.submitDeleteForm = function (buttonId = 'confirm-btn-deleteModal', formId = 'deleteForm', loadingText = 'Menghapus...') {
    var deleteBtn = document.getElementById(buttonId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + loadingText;
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    var form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
};

/* ==========================================
 * STATUS TOMBOL AKSI
 * ========================================== */

/**
 * Memperbarui status tombol hapus dan tombol print
 * berdasarkan checkbox yang dipilih.
 *
 * Alur:
 * 1. Hitung jumlah checkbox `input[name="ids[]"]` yang dicentang.
 * 2. Perbarui teks `selectedCountText`.
 * 3. Aktifkan/nonaktifkan tombol hapus beserta kelas visualnya.
 * 4. Tampilkan/sembunyikan tombol cetak terpilih.
 */
function updateButtonStates() {
    var deleteButton = document.getElementById('delete-button');
    var printSelectedItem = document.getElementById('printSelectedItem');
    var selectedCountText = document.getElementById('selectedCountText');
    var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    var count = checkedCheckboxes.length;

    if (selectedCountText) {
        selectedCountText.textContent = count;
    }

    if (deleteButton) {
        if (count > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    if (printSelectedItem) {
        if (count > 0) {
            printSelectedItem.classList.remove('hidden');
        } else {
            printSelectedItem.classList.add('hidden');
        }
    }
}

/* ==========================================
 * CETAK TERPILIH
 * ========================================== */

/**
 * Fungsi untuk print kwitansi yang dipilih.
 * Mengumpulkan checkbox yang dicentang, mengirim via AJAX, dan download PDF.
 *
 * Alur:
 * 1. Ambil route dari hidden input `kwintansi-print-selected-route`.
 * 2. Jika route kosong, hentikan proses.
 * 3. Delegasikan ke sharedPrintSelected(route, btn).
 *
 * @param {HTMLButtonElement} btn - Tombol yang diklik
 * @returns {boolean} true jika proses dimulai
 */
function printSelected(btn) {
    var printRoute = document.getElementById('kwintansi-print-selected-route');
    var route = printRoute ? printRoute.value : '';

    if (!route) return false;

    return sharedPrintSelected(route, btn);
}

// Expose ke global scope agar bisa dipanggil dari inline onclick di Blade
window.printSelected = printSelected;

/* ==========================================
 * INISIALISASI
 * ========================================== */

/**
 * Inisialisasi interaksi pada halaman index Kwitansi.
 *
 * Alur:
 * 1. Pasang handler checkbox "Pilih Semua" dan sinkronisasi checkbox individual.
 * 2. Inisialisasi status awal tombol aksi.
 * 3. Loading indicator pada submit form modal tambah & edit.
 * 4. Cegah pengiriman ganda pada form hapus (jika tombol sudah disabled).
 * 5. Reset state tombol submit ketika modal ditutup via klik backdrop.
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ─── Checkbox Pilih Semua ─── */
    var selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateButtonStates();
        });
    }

    /* ─── Listener Checkbox Individual ─── */
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateButtonStates();
        });
    });

    /* ─── Inisialisasi Status Tombol ─── */
    updateButtonStates();

    /* ─── Form Submit dengan Loading Indicator: Modal Tambah ─── */
    var addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                setButtonLoading(submitBtn, true, 'Menyimpan...');
            }
        });
    }

    /* ─── Form Submit dengan Loading Indicator: Modal Edit ─── */
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                setButtonLoading(submitBtn, true, 'Memperbarui...');
            }
        });
    });

    /* ─── Form Hapus - Cegah Pengiriman Ganda ─── */
    var deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            var submitBtn = document.getElementById('confirm-btn-deleteModal');
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
        });
    }

    /* ─── Reset State Saat Modal Backdrop Diklik ─── */
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-backdrop')) {
            document.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    });
});
