/**
 * Halaman Indeks Absensi - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Data Absensi:
 * - Checkbox Pilih Semua & hapus massal
 * - Validasi duplikat absensi di sisi klien
 * - Validasi form Tambah (pemilihan karyawan + rentang tanggal)
 * - Validasi rentang tanggal dengan koreksi otomatis
 * - Penanganan submit form Edit
 *
 * Data server dikirim lewat window.attendanceConfig (di-set di
 * pages/sdm/attendance.blade.php). Fungsi yang dipanggil dari atribut HTML
 * inline diekspos ke window karena Vite memuat JS sebagai ES module,
 * bukan global.
 */

/**
 * Konfigurasi halaman absensi dari backend.
 *
 * Berisi data yang di-set Blade, termasuk existingAttendance (hasil
 * AttendanceService::getExistingAttendance) untuk validasi duplikat.
 *
 * @type {Object<string, *>}
 */
const config = window.attendanceConfig || {};

/**
 * Data absensi yang sudah ada, dikelompokkan per kode karyawan.
 *
 * Struktur: { 'EMP001': ['2025-01-01', '2025-01-02'], ... }
 * Dipakai validasi duplikat sisi klien (validateDuplicateAttendance).
 *
 * @type {Object<string, string[]>}
 */
const existingAttendance = config.existingAttendance || {};

// ==========================================
// SELECT ALL ROW CHECKBOXES (Bulk Delete)
// ==========================================

/**
 * Memperbarui status tombol Hapus berdasarkan jumlah checkbox baris yang dicentang.
 *
 * Tombol hanya diaktifkan saat minimal satu baris terpilih; jika tidak ada
 * yang tercentang, tombol dinonaktifkan dengan kelas opacity-50 dan
 * cursor-not-allowed.
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
 * Mengirim form hapus massal dengan status memuat pada tombol konfirmasi.
 *
 * Alur:
 * - Ganti isi tombol konfirmasi dengan spinner "Menghapus..." lalu nonaktifkan.
 * - Submit form #deleteForm.
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
// VALIDASI DUPLIKAT ABSENSI (CLIENT-SIDE)
// ==========================================

/**
 * Memvalidasi kombinasi karyawan terpilih + rentang tanggal terhadap data
 * absensi yang sudah ada; menampilkan peringatan dan menonaktifkan tombol
 * submit bila ditemukan duplikat.
 *
 * Alur:
 * 1. Baca ID karyawan terpilih dari hidden input komponen searchable-multi
 *    (.searchable-multi-hidden-inputs).
 * 2. Jika tanggal atau karyawan belum lengkap, tampilkan form normal dan
 *    kembalikan true (belum perlu validasi).
 * 3. Untuk setiap karyawan, ambil label/nama dari elemen option untuk pesan
 *    yang mudah dibaca.
 * 4. Jika karyawan ada di existingAttendance, loop tanggal dari start_date
 *    sampai end_date; format Y-m-d (UTC) dibandingkan dengan array tanggal
 *    existing; tanggal yang cocok dicatat sebagai duplikat.
 * 5. Bila ada duplikat: tampilkan maksimal 5 nama + tanggal, tambahkan
 *    keterangan sisa, tampilkan peringatan, nonaktifkan tombol submit → false.
 * 6. Bila tidak ada: sembunyikan peringatan, aktifkan tombol submit → true.
 *
 * @returns {boolean} true jika tidak ada duplikat, false jika ada.
 */
function validateDuplicateAttendance() {
    var duplicateWarning = document.getElementById('duplicate-warning');
    var duplicateWarningText = document.getElementById('duplicate-warning-text');
    var addSubmitBtn = document.querySelector('#addModal button[type="submit"]');
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');

    // Get selected employee IDs from the multi-select hidden inputs
    var hiddenInputsContainer = document.querySelector('.searchable-multi-hidden-inputs');
    var hiddenInputs = hiddenInputsContainer ? hiddenInputsContainer.querySelectorAll('input[type="hidden"]') : [];
    var employeeIds = Array.from(hiddenInputs).map(function(input) { return input.value; });

    if (!startDateInput || !endDateInput || !startDateInput.value || !endDateInput.value || employeeIds.length === 0) {
        if (duplicateWarning) {
            duplicateWarning.classList.add('hidden');
        }
        if (addSubmitBtn) {
            addSubmitBtn.disabled = false;
            addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return true;
    }

    var startDate = new Date(startDateInput.value);
    var endDate = new Date(endDateInput.value);
    var duplicates = [];

    // Get employee labels from the multi-select wrapper for error messages
    var wrapper = document.querySelector('.searchable-multi-select-wrapper');
    var optionElements = wrapper ? wrapper.querySelectorAll('.searchable-multi-options .searchable-multi-option') : [];

    employeeIds.forEach(function(employeeId) {
        // Find the label for this employee
        var employeeName = employeeId;
        optionElements.forEach(function(opt) {
            if (opt.dataset.value === employeeId) {
                employeeName = opt.dataset.label.split(' - ')[0];
            }
        });

        if (existingAttendance[employeeId]) {
            var currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                var dateStr = currentDate.toISOString().split('T')[0];

                if (existingAttendance[employeeId].indexOf(dateStr) !== -1) {
                    var formattedDate = new Date(dateStr).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    duplicates.push(employeeName + ' pada tanggal ' + formattedDate);
                }

                currentDate.setDate(currentDate.getDate() + 1);
            }
        }
    });

    if (duplicates.length > 0) {
        // Kelompokkan per karyawan agar nama tidak terulang di tiap tanggal
        var grouped = {};
        duplicates.forEach(function(entry) {
            var match = entry.match(/^(.+?) pada tanggal (.+)$/);
            if (match) {
                if (!grouped[match[1]]) {
                    grouped[match[1]] = [];
                }
                grouped[match[1]].push(match[2]);
            } else {
                if (!grouped[entry]) {
                    grouped[entry] = [];
                }
                grouped[entry].push('');
            }
        });

        var groupedMessages = Object.keys(grouped).map(function(name) {
            return name + ' pada tanggal ' + grouped[name].join(', ');
        });

        var displayDuplicates = groupedMessages.slice(0, 5);
        var message = 'Karyawan berikut sudah memiliki absensi: ' + displayDuplicates.join('; ');

        if (groupedMessages.length > 5) {
            message += ' dan ' + (groupedMessages.length - 5) + ' lainnya';
        }

        message += '. Silakan hapus atau edit data yang sudah ada.';

        duplicateWarningText.textContent = message;
        duplicateWarning.classList.remove('hidden');

        if (addSubmitBtn) {
            addSubmitBtn.disabled = true;
            addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        return false;
    } else {
        if (duplicateWarning) {
            duplicateWarning.classList.add('hidden');
        }
        if (addSubmitBtn) {
            addSubmitBtn.disabled = false;
            addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return true;
    }
}

// ==========================================
// FORM VALIDATION
// ==========================================

/**
 * Menangani submit form modal Tambah: memvalidasi pemilihan karyawan dan
 * duplikat absensi sebelum form dikirim.
 *
 * Alur:
 * - Tanpa karyawan terpilih → cegah submit dan tampilkan error multi-select.
 * - Gagal validasi duplikat → cegah submit.
 * - Sukses → terapkan status memuat via handleFormSubmit; bila ditolak
 *   cegah submit.
 * - Saat checkbox multi-select berubah dan sudah ada karyawan terpilih,
 *   sembunyikan error multi-select.
 */
function initAddFormHandler() {
    var addModalForm = document.querySelector('#addModal form');
    if (!addModalForm) return;

    addModalForm.addEventListener('submit', function(e) {
        // Validate at least 1 employee is selected
        var hiddenInputsContainer = document.querySelector('.searchable-multi-hidden-inputs');
        var hiddenInputs = hiddenInputsContainer ? hiddenInputsContainer.querySelectorAll('input[type="hidden"]') : [];

        if (hiddenInputs.length === 0) {
            e.preventDefault();
            var multiSelectError = document.querySelector('.searchable-multi-error');
            if (multiSelectError) {
                multiSelectError.classList.remove('hidden');
            }
            return false;
        }

        // Validate no duplicate attendance
        if (!validateDuplicateAttendance()) {
            e.preventDefault();
            return false;
        }

        // Apply loading state to submit button
        var submitBtn = this.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn)) {
            e.preventDefault();
            return false;
        }
    });

    // Hide multi-select error when employee is selected
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('searchable-multi-checkbox') || e.target.classList.contains('searchable-multi-select-all')) {
            var hiddenInputsContainer = document.querySelector('.searchable-multi-hidden-inputs');
            var hiddenInputs = hiddenInputsContainer ? hiddenInputsContainer.querySelectorAll('input[type="hidden"]') : [];
            var multiSelectError = document.querySelector('.searchable-multi-error');

            if (hiddenInputs.length > 0 && multiSelectError) {
                multiSelectError.classList.add('hidden');
            }
        }
    });

    // Validasi ulang duplikat SETIAP kali pilihan karyawan berubah (centang
    // checkbox opsi, Pilih Semua, atau hapus tag), bukan hanya saat tanggal
    // berubah / submit. Tanpa ini peringatan "Data Absensi Sudah Ada!"
    // bertahan basi meski karyawan sebelumnya sudah dihapus & diganti.
    var addMultiSelectWrapper = document.querySelector('#addModal .searchable-multi-select-wrapper');
    if (addMultiSelectWrapper) {
        addMultiSelectWrapper.addEventListener('change', function(e) {
            if (e.target.classList.contains('searchable-multi-checkbox') ||
                e.target.classList.contains('searchable-multi-select-all')) {
                validateDuplicateAttendance();
            }
        });

        // Hapus tag via tombol × (komponen memperbarui hidden inputs pada
        // click handler yang sama, jadi validasi dijalankan setelahnya).
        addMultiSelectWrapper.addEventListener('click', function(e) {
            if (e.target.closest('.searchable-multi-tag-remove')) {
                setTimeout(validateDuplicateAttendance, 0);
            }
        });
    }
}

// ==========================================
// DATE VALIDATION
// ==========================================

/**
 * Validasi rentang tanggal (start_date/end_date) dengan koreksi otomatis.
 *
 * Alur:
 * - Saat start_date berubah: tetapkan min end_date = start_date; jika
 *   end_date < start_date, koreksi end_date = start_date; sembunyikan pesan
 *   error tanggal; jalankan ulang validateDuplicateAttendance().
 * - Saat end_date berubah: jika end_date < start_date, tampilkan pesan error
 *   lalu koreksi end_date = start_date; selain itu sembunyikan pesan error;
 *   jalankan ulang validateDuplicateAttendance().
 *
 * Validasi ulang di kedua arah memastikan peringatan duplikat selalu sinkron
 * dengan rentang tanggal terbaru yang dipilih user.
 */
function initDateValidation() {
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');

    if (!startDateInput || !endDateInput) return;

    startDateInput.addEventListener('change', function() {
        var dateError = document.getElementById('date-error');
        endDateInput.min = this.value;

        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
        if (dateError) {
            dateError.classList.add('hidden');
        }
        validateDuplicateAttendance();
    });

    endDateInput.addEventListener('change', function() {
        var dateError = document.getElementById('date-error');
        if (startDateInput.value && this.value < startDateInput.value) {
            if (dateError) {
                dateError.classList.remove('hidden');
            }
            this.value = startDateInput.value;
        } else {
            if (dateError) {
                dateError.classList.add('hidden');
            }
        }
        validateDuplicateAttendance();
    });
}

// ==========================================
// EDIT MODAL FORM HANDLERS
// ==========================================

/**
 * Menangani submit semua form modal Edit dengan status memuat.
 *
 * Untuk tiap form [id^="editModal-"]: terapkan handleFormSubmit pada tombol
 * submit; bila ditolak, cegah pengiriman (anti double submit).
 */
function initEditFormHandlers() {
    document.querySelectorAll('[id^="editModal-"] form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var submitBtn = this.querySelector('button[type="submit"]');
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
 * Menginisialisasi seluruh fungsionalitas halaman absensi saat DOM siap.
 *
 * Alur inisialisasi:
 * - Checkbox "Pilih Semua": centang/batalkan semua checkbox baris lalu
 *   perbarui tombol hapus.
 * - Checkbox baris: perbarui status Pilih Semua (tercentang bila semua
 *   baris terpilih) dan tombol hapus.
 * - Perbarui status tombol hapus di awal (halaman dimuat).
 * - Daftarkan initAddFormHandler, initDateValidation, initEditFormHandlers.
 * - Inisialisasi searchable single-select untuk modal edit.
 */
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAll');

    if (selectAll) {
        // Select All checkbox - toggles all row checkboxes for bulk delete
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateDeleteButtonState();
        });
    }

    // Individual row checkbox - updates Select All state and delete button
    document.querySelectorAll('input[name="ids[]"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateDeleteButtonState();
        });
    });

    // Initialize delete button state on page load
    updateDeleteButtonState();

    initAddFormHandler();
    initDateValidation();
    initEditFormHandlers();

    // Initialize searchable single-select components (used in edit modal)
    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }
});
