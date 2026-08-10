/**
 * Halaman Indeks Lembur - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Data Lembur:
 * - Inisialisasi Searchable Select
 * - Validasi duplikat absensi di sisi klien
 * - Logika checkbox Pilih Semua
 * - Perhitungan total lembur (jam × tarif)
 * - Manajemen status tombol hapus
 * - Penanganan submit form dengan status memuat
 */

// ==========================================
// SEARCHABLE SELECT INITIALIZATION
// ==========================================

/**
 * Menginisialisasi komponen searchable select saat DOM siap.
 *
 * Modul bersama searchable-select.js menyediakan initSearchableSelects().
 * Inisialisasi ulang setelah DOM siap agar select pada semua modal berfungsi.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }
});

// ==========================================
// CLIENT-SIDE DUPLICATE VALIDATION
// ==========================================

/**
 * Data absensi yang sudah ada, di-seed dari PHP via Blade
 * (window.overtimeExistingAttendance).
 *
 * Struktur: { 'EMP001': { '2025-01-01': { id: 1, status: 'hadir' }, ... }, ... }
 * Disiapkan oleh OvertimeService::getExistingAttendance dan dipakai untuk
 * mencegah:
 * - Duplikat lembur (karyawan + tanggal sama dengan status 'lembur')
 * - Lembur untuk karyawan berstatus izin/sakit/cuti
 *
 * @type {Object<string, Object<string, {id: number, status: string}>>}
 */
const existingAttendance = window.overtimeExistingAttendance || {};

/**
 * Memeriksa apakah sebuah tanggal (format Y-m-d) jatuh pada hari Minggu.
 *
 * @param {string} dateStr Tanggal berformat Y-m-d.
 * @return {boolean} true jika hari Minggu.
 */
function isSunday(dateStr) {
    if (!dateStr) return false;
    return new Date(dateStr + 'T00:00:00').getDay() === 0;
}

// --- Add Modal Duplicate Validation ---

/**
 * Memvalidasi form lembur Tambah terhadap data absensi yang sudah ada.
 *
 * Alur:
 * 1. Baca employee_id (hidden searchable-select) dan tanggal dari modal Tambah.
 * 2. Jika kosong → sembunyikan peringatan dan izinkan (belum bisa divalidasi).
 * 3. Cari existingAttendance[employeeId][date]:
 *    - status 'lembur' → blokir submit (data lembur sudah ada untuk
 *      karyawan + tanggal tersebut).
 *    - status 'izin'/'sakit'/'cuti' → blokir submit (lembur hanya untuk
 *      karyawan yang hadir).
 * 4. Tidak ada record / status 'hadir' → izinkan dan sembunyikan peringatan.
 *
 * @returns {boolean} true jika valid, false jika diblokir.
 */
function validateAddOvertime() {
    const addEmployeeHidden = document.querySelector('#addModal .searchable-select-hidden');
    const addDateInput = document.getElementById('add-attendance-date');
    const addDuplicateWarning = document.getElementById('add-duplicate-warning');
    const addDuplicateWarningText = document.getElementById('add-duplicate-warning-text');
    const addSubmitBtn = document.querySelector('#addModal button[type="submit"]');

    if (!addEmployeeHidden || !addDateInput) return true;

    const employeeId = addEmployeeHidden.value;
    const date = addDateInput.value;

    if (!employeeId || !date) {
        hideAddDuplicateWarning(addSubmitBtn);
        return true;
    }

    if (isSunday(date)) {
        showAddDuplicateWarning(
            addDuplicateWarning,
            addDuplicateWarningText,
            addSubmitBtn,
            'Hari Minggu adalah hari libur. Lembur tidak dapat diinput pada hari Minggu. Silakan pilih hari Senin sampai Sabtu.'
        );
        return false;
    }

    if (existingAttendance[employeeId] && existingAttendance[employeeId][date]) {
        const existing = existingAttendance[employeeId][date];
        const employeeInput = document.querySelector('#addModal .searchable-select-input');
        const employeeName = employeeInput ? employeeInput.value : '';
        const formattedDate = formatDateIndonesian(date);

        if (existing.status === 'lembur') {
            showAddDuplicateWarning(
                addDuplicateWarning,
                addDuplicateWarningText,
                addSubmitBtn,
                `Karyawan ${employeeName} sudah memiliki data lembur pada tanggal ${formattedDate}. Silakan pilih tanggal lain atau edit data yang sudah ada.`
            );
            return false;
        }

        if (['izin', 'sakit', 'cuti'].includes(existing.status)) {
            showAddDuplicateWarning(
                addDuplicateWarning,
                addDuplicateWarningText,
                addSubmitBtn,
                `Karyawan ${employeeName} memiliki status ${existing.status.toUpperCase()} pada tanggal ${formattedDate}. Lembur hanya bisa ditambahkan untuk karyawan yang hadir.`
            );
            return false;
        }
    }

    hideAddDuplicateWarning(addSubmitBtn);
    return true;
}

/**
 * Menampilkan banner peringatan duplikat pada modal Tambah.
 *
 * Mengisi teks peringatan, menampilkan elemen warning, dan menonaktifkan
 * tombol submit agar form tidak bisa dikirim.
 *
 * @param {HTMLElement} warningEl  Elemen kontainer peringatan.
 * @param {HTMLElement} textEl     Elemen teks peringatan.
 * @param {HTMLElement} submitBtn  Tombol submit yang dinonaktifkan.
 * @param {string}      message    Pesan peringatan yang ditampilkan.
 */
function showAddDuplicateWarning(warningEl, textEl, submitBtn, message) {
    textEl.textContent = message;
    warningEl.classList.remove('hidden');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

/**
 * Menyembunyikan banner peringatan duplikat pada modal Tambah dan
 * mengaktifkan kembali tombol submit.
 *
 * @param {HTMLElement} submitBtn  Tombol submit yang diaktifkan kembali.
 */
function hideAddDuplicateWarning(submitBtn) {
    const addDuplicateWarning = document.getElementById('add-duplicate-warning');
    if (addDuplicateWarning) {
        addDuplicateWarning.classList.add('hidden');
    }
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

/**
 * Menginisialisasi pendengar validasi duplikat pada modal Tambah.
 *
 * Alur:
 * - MutationObserver memantau atribut value hidden input searchable-select;
 *   setiap perubahan memicu validateAddOvertime() (komponen searchable-select
 *   memperbarui hidden input tanpa event input/change biasa).
 * - Event change pada input tanggal juga memicu validateAddOvertime().
 */
function initAddModalValidation() {
    const addEmployeeHidden = document.querySelector('#addModal .searchable-select-hidden');
    const addDateInput = document.getElementById('add-attendance-date');

    if (!addEmployeeHidden || !addDateInput) return;

    // Watch hidden input changes via MutationObserver (searchable-select updates hidden input)
    const searchableWrapper = addEmployeeHidden.closest('.searchable-select-wrapper');
    if (searchableWrapper) {
        const observer = new MutationObserver(function () {
            validateAddOvertime();
        });
        observer.observe(addEmployeeHidden, { attributes: true, attributeFilter: ['value'] });
    }

    addDateInput.addEventListener('change', validateAddOvertime);
}

// --- Edit Modal Duplicate Validation ---

/**
 * Menginisialisasi validasi duplikat untuk semua modal Edit.
 *
 * Setiap modal edit memiliki input tanggal dengan data attribute
 * (data-overtime-id, data-original-date) untuk keperluan validasi.
 *
 * Alur per modal (lihat validateEditOvertime):
 * 1. Tanggal kosong → izinkan.
 * 2. Tanggal sama dengan tanggal asli (tidak berubah) → izinkan.
 * 3. Cek existingAttendance[employeeId][date]:
 *    - record id yang sama (data yang sedang diedit) → izinkan.
 *    - status 'lembur' → blokir (duplikat).
 *    - status izin/sakit/cuti → blokir (lembur hanya untuk hadir).
 * 4. Selain itu → izinkan dan sembunyikan peringatan.
 */
function initEditModalValidation() {
    document.querySelectorAll('[id^="edit-attendance-date-"]').forEach(function (dateInput) {
        var overtimeId = dateInput.dataset.overtimeId;
        var originalDate = dateInput.dataset.originalDate;
        var employeeInput = dateInput.closest('form').querySelector('input[name="employee_id"]');
        var employeeId = employeeInput ? employeeInput.value : null;
        var duplicateWarning = document.getElementById('edit-duplicate-warning-' + overtimeId);
        var duplicateWarningText = document.getElementById('edit-duplicate-warning-text-' + overtimeId);
        var submitBtn = document.querySelector('#editModal-' + overtimeId + ' button[type="submit"]');

        /**
         * Memvalidasi satu input tanggal edit terhadap data existing.
         *
         * @returns {boolean} true jika valid, false jika diblokir.
         */
        function validateEditOvertime() {
            var date = dateInput.value;

            if (!date) {
                hideEditDuplicateWarning(duplicateWarning, submitBtn);
                return true;
            }

            // If the date hasn't changed from original, no validation needed
            if (date === originalDate) {
                hideEditDuplicateWarning(duplicateWarning, submitBtn);
                return true;
            }

            // Minggu adalah hari libur — lembur tidak boleh diinput.
            if (isSunday(date)) {
                showEditDuplicateWarning(
                    duplicateWarning,
                    duplicateWarningText,
                    submitBtn,
                    'Hari Minggu adalah hari libur. Lembur tidak dapat diinput pada hari Minggu. Silakan pilih hari Senin sampai Sabtu.'
                );
                return false;
            }

            // Check if employee + date combination already exists
            if (existingAttendance[employeeId] && existingAttendance[employeeId][date]) {
                var existing = existingAttendance[employeeId][date];

                // Skip if the record ID matches (it's the same record being edited)
                if (existing.id != overtimeId) {
                    var employeeName = dateInput.closest('form').querySelector('input[type="text"]').value;
                    var formattedDate = formatDateIndonesian(date);

                    if (existing.status === 'lembur') {
                        showEditDuplicateWarning(
                            duplicateWarning,
                            duplicateWarningText,
                            submitBtn,
                            `Karyawan ${employeeName} sudah memiliki data lembur pada tanggal ${formattedDate}. Silakan pilih tanggal lain atau hapus data yang sudah ada.`
                        );
                        return false;
                    }

                    if (['izin', 'sakit', 'cuti'].includes(existing.status)) {
                        showEditDuplicateWarning(
                            duplicateWarning,
                            duplicateWarningText,
                            submitBtn,
                            `Karyawan ${employeeName} memiliki status ${existing.status.toUpperCase()} pada tanggal ${formattedDate}. Lembur hanya bisa ditambahkan untuk karyawan yang hadir.`
                        );
                        return false;
                    }
                }
            }

            hideEditDuplicateWarning(duplicateWarning, submitBtn);
            return true;
        }

        dateInput.addEventListener('change', validateEditOvertime);
    });
}

/**
 * Menampilkan banner peringatan duplikat pada modal Edit.
 *
 * Mengisi teks peringatan, menampilkan elemen warning, dan menonaktifkan
 * tombol submit agar form tidak bisa dikirim.
 *
 * @param {HTMLElement} warningEl  Elemen kontainer peringatan.
 * @param {HTMLElement} textEl     Elemen teks peringatan.
 * @param {HTMLElement} submitBtn  Tombol submit yang dinonaktifkan.
 * @param {string}      message    Pesan peringatan yang ditampilkan.
 */
function showEditDuplicateWarning(warningEl, textEl, submitBtn, message) {
    textEl.textContent = message;
    warningEl.classList.remove('hidden');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

/**
 * Menyembunyikan banner peringatan duplikat pada modal Edit dan
 * mengaktifkan kembali tombol submit.
 *
 * @param {HTMLElement} warningEl  Elemen kontainer peringatan.
 * @param {HTMLElement} submitBtn  Tombol submit yang diaktifkan kembali.
 */
function hideEditDuplicateWarning(warningEl, submitBtn) {
    if (warningEl) {
        warningEl.classList.add('hidden');
    }
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// ==========================================
// SELECT ALL CHECKBOX
// ==========================================

/**
 * Menginisialisasi checkbox "Pilih Semua" dan pendengar checkbox individu.
 *
 * Alur:
 * - Pilih Semua: centang/batalkan semua checkbox baris lalu perbarui tombol hapus.
 * - Checkbox individu: perbarui status Pilih Semua (tercentang bila semua
 *   baris tercentang) dan tombol hapus.
 */
function initSelectAllCheckbox() {
    var selectAll = document.getElementById('selectAll');
    if (!selectAll) return;

    selectAll.addEventListener('change', function () {
        var checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
        updateDeleteButtonState();
    });

    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            selectAll.checked = allCheckboxes.length === checkedCheckboxes.length;
            updateDeleteButtonState();
        });
    });
}

// ==========================================
// OVERTIME TOTAL CALCULATION
// ==========================================

/**
 * Memformat nilai input field sebagai mata uang IDR (misalnya, 15000 -> "15.000").
 * Menghapus semua karakter non-digit dan memformat ulang dengan lokal Indonesia.
 *
 * Ditugaskan ke window karena dipanggil dari atribut oninput inline
 * di template Blade (Vite memuat JS sebagai ES module, bukan global).
 *
 * @param {HTMLInputElement} input - Elemen input yang akan diformat.
 */
window.formatCurrencyInput = function (input) {
    if (!input) return;

    var numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
};

/**
 * Mengurai string mata uang yang sudah diformat menjadi integer mentah.
 * Menangani format Indonesia dengan titik sebagai pemisah ribuan dan
 * tanda minus untuk nilai negatif.
 *
 * @param  {string} value - String mata uang yang sudah diformat (misalnya, "15.000").
 * @returns {number} Nilai integer mentah (misalnya, 15000).
 */
function parseCurrencyInput(value) {
    var rawValue = String(value || '').trim();

    if (!rawValue) {
        return 0;
    }

    return parseInt(rawValue.replace(/[^0-9-]/g, ''), 10) || 0;
}

/**
 * Menghitung total lembur pada modal Tambah.
 *
 * Alur:
 * - Baca jam lembur (parseFloat) dan tarif per jam (parseCurrencyInput).
 * - total = jam × tarif.
 * - Tampilkan hasil berformat 'Rp ...' (locale id-ID) pada field total.
 *
 * Ditugaskan ke window karena dipanggil dari atribut oninput inline.
 */
window.calculateAddOvertimeTotal = function () {
    var addHoursInput = document.getElementById('add-overtime-hours');
    var addRateInput = document.getElementById('add-overtime-rate');
    var addTotalInput = document.getElementById('add-overtime-total');

    if (!addHoursInput || !addRateInput || !addTotalInput) return;

    var hours = parseFloat(addHoursInput.value) || 0;
    var rate = parseCurrencyInput(addRateInput.value);
    var total = hours * rate;

    addTotalInput.value = 'Rp ' + total.toLocaleString('id-ID');
};

/**
 * Menghitung total lembur pada modal Edit tertentu.
 *
 * Alur:
 * - Baca jam lembur dan tarif dari input modal dengan id
 *   edit-overtime-hours-{id} / edit-overtime-rate-{id}.
 * - total = jam × tarif; hasil ditulis ke edit-overtime-total-{id}
 *   berformat 'Rp ...' (locale id-ID).
 *
 * Ditugaskan ke window karena dipanggil dari atribut oninput inline.
 *
 * @param {string} id - ID record lembur (menargetkan modal edit yang benar).
 */
window.calculateEditOvertimeTotal = function (id) {
    var hoursInput = document.getElementById('edit-overtime-hours-' + id);
    var rateInput = document.getElementById('edit-overtime-rate-' + id);
    var totalInput = document.getElementById('edit-overtime-total-' + id);

    if (!hoursInput || !rateInput || !totalInput) return;

    var hours = parseFloat(hoursInput.value) || 0;
    var rate = parseCurrencyInput(rateInput.value);
    var total = hours * rate;

    totalInput.value = 'Rp ' + total.toLocaleString('id-ID');
};

// ==========================================
// DELETE BUTTON STATE
// ==========================================

/**
 * Memperbarui status tombol Hapus berdasarkan checkbox yang dicentang.
 *
 * Tombol diaktifkan saat minimal satu checkbox tercentang dan dinonaktifkan
 * (opacity-50, cursor-not-allowed) saat tidak ada yang tercentang.
 */
function updateDeleteButtonState() {
    var deleteButton = document.getElementById('delete-button');
    var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

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

/**
 * Mengirim form hapus massal dengan status memuat.
 *
 * Menampilkan spinner pada tombol konfirmasi lalu mengirim form #deleteForm.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onclick inline pada
 * modal konfirmasi hapus (Vite memuat JS sebagai ES module, bukan global).
 */
window.submitDeleteForm = function () {
    var deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
};

// ==========================================
// FORM SUBMIT HANDLERS
// ==========================================

/**
 * Menginisialisasi handler submit untuk form modal Tambah dan Edit.
 *
 * Alur:
 * - Form Tambah: validasi duplikat dulu (validateAddOvertime), lalu terapkan
 *   status memuat via handleFormSubmit; bila ditolak, cegah pengiriman.
 * - Form Edit: terapkan status memuat via handleFormSubmit; bila ditolak,
 *   cegah pengiriman.
 */
function initFormSubmitHandlers() {
    // Add modal submit handler
    var addOvertimeForm = document.querySelector('#addModal form');
    if (addOvertimeForm) {
        addOvertimeForm.addEventListener('submit', function (e) {
            if (!validateAddOvertime()) {
                e.preventDefault();
                return false;
            }

            var submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Edit modal submit handlers
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Memformat string tanggal (Y-m-d) ke format lokal Indonesia (dd/mm/yyyy).
 *
 * @param  {string}  dateStr  String tanggal format Y-m-d.
 * @returns {string} String tanggal terformat (dd/mm/yyyy).
 */
function formatDateIndonesian(dateStr) {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Menginisialisasi semua fungsionalitas halaman lembur saat DOM siap.
 *
 * Alur inisialisasi: initAddModalValidation, initEditModalValidation,
 * initSelectAllCheckbox, initFormSubmitHandlers, dan inisialisasi status
 * tombol hapus.
 */
document.addEventListener('DOMContentLoaded', function () {
    initAddModalValidation();
    initEditModalValidation();
    initSelectAllCheckbox();
    initFormSubmitHandlers();
    updateDeleteButtonState();
});
