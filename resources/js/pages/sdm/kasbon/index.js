/**
 * Halaman Indeks Kasbon - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Data Kasbon:
 * - Toggle jenis (pribadi/tim)
 * - Pengecekan kasbon maksimal melalui AJAX
 * - Penyelesaian tanggal periode melalui AJAX
 * - Validasi jumlah terhadap batas maksimal
 * - Format mata uang
 * - Logika checkbox Pilih Semua
 * - Hapus massal dengan status memuat
 * - Penanganan pengiriman formulir dengan status memuat
 */

// ==========================================
// KONFIGURASI
// ==========================================

/**
 * Container halaman kasbon dan URL rute yang dibutuhkan untuk AJAX.
 *
 * Dibaca dari atribut data yang di-set template Blade agar URL tidak
 * di-hardcode di dalam JS (lihat pageUrl()).
 *
 * @type {HTMLElement|null}
 */
const pageContainer = document.getElementById('kasbon-page');

/**
 * URL endpoint payroll.get-weeks untuk penyelesaian tanggal periode.
 *
 * Diperoleh lewat pageUrl() dengan fallback '/payroll/weeks'.
 *
 * @type {string}
 */
const GET_WEEKS_URL = pageUrl('payroll.get-weeks');

// ==========================================
// HELPER MATA UANG
// ==========================================

/**
 * Mengurai string mata uang yang sudah diformat menjadi integer mentah.
 * Menangani format Indonesia dengan titik sebagai pemisah ribuan.
 *
 * @param  {string} value - String mata uang yang sudah diformat (misalnya, "15.000")
 * @returns {number} Nilai integer mentah (misalnya, 15000)
 */
function parseCurrencyInput(value) {
    return parseInt(String(value || '').replace(/[^\d]/g, ''), 10) || 0;
}

/**
 * Memformat nilai input field sebagai mata uang IDR (misalnya, 15000 -> "15.000").
 * Menghapus semua karakter non-digit dan memformat ulang dengan lokal Indonesia.
 *
 * Ditugaskan ke window karena dipanggil dari atribut oninput inline
 * di template Blade (Vite memuat JS sebagai ES module, bukan global).
 *
 * @param {HTMLInputElement} input - Element input yang akan diformat
 */
window.formatCurrencyInput = function (input) {
    if (!input) return;

    const numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
};

// ==========================================
// TOGGLE JENIS (Pribadi / Tim)
// ==========================================

/**
 * Menampilkan/menyembunyikan field karyawan dan divisi sesuai jenis kasbon.
 *
 * Alur:
 * - 'team': sembunyikan field karyawan, tampilkan field divisi; hapus
 *   required + kosongkan hidden karyawan; set required pada hidden divisi;
 *   inisialisasi ulang searchable select divisi; sembunyikan alert batas.
 * - 'personal': kebalikannya (tampilkan karyawan, sembunyikan divisi,
 *   set required pada karyawan, hidden divisi dibersihkan, init select
 *   karyawan).
 * - kosong: sembunyikan keduanya beserta alert batas.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onchange inline.
 *
 * @param {string} prefix - Awalan id elemen ('add' atau 'edit_KSB001').
 */
window.toggleEmployeeSelect = function (prefix) {
    const kasbonTypeSelect = document.getElementById(prefix + '_kasbon_type');
    const employeeField = document.getElementById(prefix + '_employee_field');
    const employeeHidden = employeeField ? employeeField.querySelector('.searchable-select-hidden') : null;
    const divisionField = document.getElementById(prefix + '_division_field');
    const divisionHidden = divisionField ? divisionField.querySelector('.searchable-select-hidden') : null;
    const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');

    if (!kasbonTypeSelect || !employeeField || !divisionField) return;

    if (kasbonTypeSelect.value === 'team') {
        employeeField.style.display = 'none';
        divisionField.style.display = 'block';
        if (limitAlert) limitAlert.classList.add('hidden');

        if (employeeHidden) {
            employeeHidden.removeAttribute('required');
            employeeHidden.value = '';
        }
        if (divisionHidden) {
            divisionHidden.setAttribute('required', 'required');
        }
        if (typeof initSearchableSelects === 'function') {
            initSearchableSelects(divisionField);
        }
    } else if (kasbonTypeSelect.value === 'personal') {
        employeeField.style.display = 'block';
        divisionField.style.display = 'none';

        if (employeeHidden) {
            employeeHidden.setAttribute('required', 'required');
        }
        if (divisionHidden) {
            divisionHidden.removeAttribute('required');
            divisionHidden.value = '';
        }
        if (typeof initSearchableSelects === 'function') {
            initSearchableSelects(employeeField);
        }
    } else {
        employeeField.style.display = 'none';
        divisionField.style.display = 'none';
        if (limitAlert) limitAlert.classList.add('hidden');
    }
};

// ==========================================
// PENYELESAIAN TANGGAL PERIODE (AJAX)
// ==========================================

/**
 * Menyelesaikan period_start_date/period_end_date + week_number dari bulan,
 * tahun, dan tanggal kasbon yang dipilih.
 *
 * Alur:
 * 1. Baca bulan, tahun, dan kasbon_date dari input form.
 * 2. Jika salah satu kosong → kembalikan null.
 * 3. AJAX GET ke GET_WEEKS_URL (rute payroll.get-weeks) dengan query
 *    month & year.
 * 4. Iterasi minggu yang dikembalikan; minggu pertama yang rentang
 *    start_date..end_date mengandung kasbon_date menjadi hasil.
 * 5. Jika tidak ada yang cocok, gunakan minggu terakhir sebagai fallback.
 * 6. Kembalikan { start_date, end_date, week_number }; null saat error/kosong.
 *
 * @param  {string} prefix - Awalan id elemen ('add' atau 'edit_KSB001').
 * @returns {Promise<{start_date: string, end_date: string, week_number: number}|null>}
 */
async function resolvePeriodStartDate(prefix) {
    const monthSelect = document.getElementById(prefix + '_period_month');
    const yearInput = document.getElementById(prefix + '_period_year');
    const kasbonDateInput = document.getElementById(prefix + '_kasbon_date');

    const month = monthSelect ? monthSelect.value : '';
    const year = yearInput ? yearInput.value : '';
    const kasbonDate = kasbonDateInput ? kasbonDateInput.value : '';

    if (!month || !year || !kasbonDate) return null;

    try {
        const response = await fetch(`${GET_WEEKS_URL}?month=${month}&year=${year}`);
        const data = await response.json();
        const weeks = data.weeks || [];

        for (const week of weeks) {
            if (kasbonDate >= week.start_date && kasbonDate <= week.end_date) {
                return {
                    start_date: week.start_date,
                    end_date: week.end_date,
                    week_number: week.week_number,
                };
            }
        }

        if (weeks.length > 0) {
            const lastWeek = weeks[weeks.length - 1];
            return {
                start_date: lastWeek.start_date,
                end_date: lastWeek.end_date,
                week_number: lastWeek.week_number,
            };
        }

        return null;
    } catch (error) {
        console.error('Error resolving period start date:', error);
        return null;
    }
}

// ==========================================
// PENGECEKAN KASBON MAKSIMAL (AJAX)
// ==========================================

/**
 * Memperbarui periode kasbon (week_number, tanggal mulai, tanggal akhir)
 * sesuai bulan/tahun/tanggal kasbon yang dipilih.
 *
 * Alur:
 * 1. Panggil resolvePeriodStartDate(prefix) yang melakukan AJAX ke
 *    payroll.get-weeks untuk mencari minggu yang memuat kasbon_date.
 * 2. Jika tidak ada hasil → keluar tanpa mengubah input.
 * 3. Isi hidden week_number, period_start_date, dan period_end_date dengan
 *    hasil periode.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onchange inline.
 *
 * @param {string} prefix - Awalan id elemen ('add' atau 'edit_KSB001').
 */
window.checkMaxKasbon = async function (prefix) {
    const weekNumberInput = document.getElementById(prefix + '_week_number');

    const periodInfo = await resolvePeriodStartDate(prefix);
    if (!periodInfo) return;

    if (weekNumberInput) {
        weekNumberInput.value = periodInfo.week_number;
    }

    const periodStartDateInput = document.getElementById(prefix + '_period_start_date');
    const periodEndDateInput = document.getElementById(prefix + '_period_end_date');
    if (periodStartDateInput) {
        periodStartDateInput.value = periodInfo.start_date;
    }
    if (periodEndDateInput) {
        periodEndDateInput.value = periodInfo.end_date;
    }
};

// ==========================================
// HAPUS MASSAL
// ==========================================

/**
 * Mengirim formulir hapus massal dengan status memuat.
 * Menampilkan spinner pada tombol konfirmasi saat mengirim.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onclick inline
 * di modal konfirmasi hapus.
 */
window.submitDeleteForm = function () {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById('deleteForm');
    if (form) {
        form.submit();
    }
};

// ==========================================
// CHECKBOX PILIH SEMUA
// ==========================================

/**
 * Menginisialisasi checkbox "Pilih Semua" dan pendengar checkbox individu.
 *
 * Alur:
 * - Pilih Semua: centang semua checkbox baris yang tidak disabled lalu
 *   perbarui tombol hapus.
 * - Checkbox individu: perbarui tombol hapus dan status Pilih Semua
 *   (tercentang bila semua baris aktif tercentang).
 */
function initSelectAllCheckbox() {
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const deleteButton = document.getElementById('delete-button')
        || document.querySelector('[onclick*="deleteModal"]')
        || document.querySelector('[data-delete-button]');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowCheckboxes.forEach(checkbox => {
                if (!checkbox.disabled) {
                    checkbox.checked = this.checked;
                }
            });
            updateDeleteButtonState();
        });
    }

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateDeleteButtonState();

            if (selectAll) {
                const allChecked = Array.from(rowCheckboxes)
                    .filter(cb => !cb.disabled)
                    .every(cb => cb.checked);
                selectAll.checked = allChecked;
            }
        });
    });

    updateDeleteButtonState();
}

/**
 * Memperbarui status tombol hapus berdasarkan checkbox baris yang dicentang.
 *
 * Tombol diaktifkan bila minimal satu checkbox aktif tercentang; selain itu
 * dinonaktifkan dengan kelas opacity-50 dan cursor-not-allowed.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button')
        || document.querySelector('[onclick*="deleteModal"]')
        || document.querySelector('[data-delete-button]');
    const anyChecked = Array.from(document.querySelectorAll('.row-checkbox'))
        .some(cb => cb.checked && !cb.disabled);

    if (deleteButton) {
        if (anyChecked) {
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.disabled = false;
        } else {
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.disabled = true;
        }
    }
}

// ==========================================
// PENANGANAN PENGIRIMAN FORMULIR
// ==========================================

/**
 * Menginisialisasi penanganan submit form Tambah dan Edit kasbon.
 *
 * Menerapkan handleFormSubmit() untuk status memuat dan mencegah double
 * submit; bila ditolak, pengiriman dibatalkan.
 */
function initFormSubmitHandlers() {
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    document.querySelectorAll('[id^="editModalK"] form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// FORMAT INPUT JUMLAH
// ==========================================

/**
 * Menginisialisasi format mata uang pada semua input jumlah kasbon.
 *
 * Input dengan kelas .kasbon-amount-input diformat saat pertama kali dimuat
 * (bila sudah berisi nilai) dan setiap kali user mengetik.
 */
function initAmountFormatting() {
    document.querySelectorAll('.kasbon-amount-input').forEach(input => {
        if (input.value) {
            window.formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            window.formatCurrencyInput(this);
        });
    });
}

// ==========================================
// HELPER UI
// ==========================================

/**
 * Mendapatkan URL untuk nama rute Laravel.
 *
 * Alur:
 * - Peta nama rute → URL: 'payroll.get-weeks' dibaca dari
 *   pageContainer.dataset.urlGetWeeks (di-set template Blade), fallback
 *   '/payroll/weeks'.
 * - Rute yang tidak dikenal mengembalikan '#'.
 *
 * @param  {string} routeName  Nama rute Laravel.
 * @returns {string} URL rute.
 */
function pageUrl(routeName) {
    const urlMap = {
        'payroll.get-weeks': pageContainer?.dataset.urlGetWeeks || '/payroll/weeks',
    };
    return urlMap[routeName] || '#';
}

// ==========================================
// INISIALISASI
// ==========================================

/**
 * Menginisialisasi semua fungsionalitas halaman kasbon saat DOM siap.
 *
 * Alur inisialisasi:
 * - initSelectAllCheckbox, initFormSubmitHandlers, initAmountFormatting.
 * - toggleEmployeeSelect('add') untuk menyetel tampilan awal form Tambah.
 * - Inisialisasi komponen searchable select.
 */
document.addEventListener('DOMContentLoaded', function () {
    initSelectAllCheckbox();
    initFormSubmitHandlers();
    initAmountFormatting();

    toggleEmployeeSelect('add');

    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }
});
