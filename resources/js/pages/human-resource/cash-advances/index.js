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
 * Membaca token CSRF dan URL rute dari atribut data pada container halaman.
 * Ini diatur oleh template Blade untuk menghindari hardcoding URL di JS.
 */
const pageContainer = document.getElementById('kasbon-page');
const CSRF_TOKEN = pageContainer ? pageContainer.dataset.csrfToken : '';
const CHECK_MAX_URL = pageContainer ? pageUrl('kasbon.check-max') : '';
const GET_WEEKS_URL = pageUrl('payroll.get-weeks');

/**
 * Menyimpan data kasbon maksimal untuk setiap awalan formulir (add/edit_KSB001).
 * Digunakan oleh validateKasbonAmount untuk memeriksa terhadap batas.
 */
let maxKasbonData = {};

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
 * Mengubah visibilitas field karyawan dan divisi berdasarkan jenis kasbon.
 *
 * Untuk 'pribadi': menampilkan select karyawan, menyembunyikan select divisi.
 * Untuk 'tim': menampilkan select divisi, menyembunyikan select karyawan.
 * Untuk kosong: menyembunyikan keduanya.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onchange inline.
 *
 * @param {string} prefix - Awalan formulir ('add' atau 'edit_KSB001')
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
 * Menyelesaikan period_start_date dan period_end_date dari bulan, tahun, dan kasbon_date.
 *
 * Mengambil minggu yang tersedia dari endpoint Payroll dan menemukan minggu
 * yang rentang tanggalnya mengandung kasbon_date.
 *
 * @param  {string} prefix - Awalan formulir ('add' atau 'edit_KSB001')
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
 * Memeriksa kasbon maksimal yang diizinkan untuk karyawan yang dipilih berdasarkan absensi.
 *
 * Menyelesaikan tanggal awal periode terlebih dahulu, lalu memanggil endpoint check-max.
 * Memperbarui UI peringatan batas dan menyimpan hasil di maxKasbonData.
 * Menonaktifkan tombol kirim jika payroll sudah dibayar atau tidak ada absensi.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onchange inline.
 *
 * @param {string} prefix - Awalan formulir ('add' atau 'edit_KSB001')
 */
window.checkMaxKasbon = async function (prefix) {
    const employeeField = document.getElementById(prefix + '_employee_field');
    const employeeHidden = employeeField ? employeeField.querySelector('.searchable-select-hidden') : null;
    const employeeSelect = employeeHidden || document.getElementById(prefix + '_employee_id');
    const kasbonDateInput = document.getElementById(prefix + '_kasbon_date');
    const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');
    const limitMessage = document.getElementById(prefix + '_kasbon_limit_message');
    const amountInput = document.getElementById(prefix + '_amount');
    const weekNumberInput = document.getElementById(prefix + '_week_number');

    if (!employeeSelect || !employeeSelect.value) {
        if (limitAlert) limitAlert.classList.add('hidden');
        maxKasbonData[prefix] = null;
        return;
    }

    const kasbonDate = kasbonDateInput ? kasbonDateInput.value : '';

    const periodInfo = await resolvePeriodStartDate(prefix);
    if (!periodInfo) {
        if (limitAlert && limitMessage) {
            limitMessage.textContent = 'Silakan lengkapi Bulan, Tahun, dan Tanggal Kasbon terlebih dahulu';
            setAlertStyle(limitAlert, 'error');
        }
        maxKasbonData[prefix] = null;
        return;
    }

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

    try {
        const response = await fetch(CHECK_MAX_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                employee_id: employeeSelect.value,
                period_start_date: periodInfo.start_date,
                kasbon_date: kasbonDate,
            }),
        });

        const data = await response.json();

        if (data.success) {
            maxKasbonData[prefix] = data;

            if (limitAlert && limitMessage) {
                limitMessage.textContent = data.message;
                setAlertStyle(limitAlert, 'warning');
            }

            if (amountInput && amountInput.value) {
                validateKasbonAmount(prefix);
            }
        } else {
            maxKasbonData[prefix] = null;

            if (limitAlert && limitMessage) {
                limitMessage.textContent = data.message || 'Gagal mengecek maksimal kasbon';
                setAlertStyle(limitAlert, 'error');
            }

            disableSubmitButton(prefix, true);
        }
    } catch (error) {
        console.error('Error checking max kasbon:', error);
        if (limitAlert) limitAlert.classList.add('hidden');
        maxKasbonData[prefix] = null;
    }
};

// ==========================================
// VALIDASI JUMLAH
// ==========================================

/**
 * Memvalidasi jumlah kasbon terhadap batas maksimal yang diizinkan.
 *
 * Jika jumlah melebihi maksimal, menonaktifkan tombol kirim dan menampilkan peringatan error.
 * Jika jumlah valid, mengaktifkan tombol kirim dan menampilkan peringatan info.
 *
 * Ditugaskan ke window karena dipanggil dari atribut oninput inline.
 *
 * @param {string} prefix - Awalan formulir ('add' atau 'edit_KSB001')
 */
window.validateKasbonAmount = function (prefix) {
    const amountInput = document.getElementById(prefix + '_amount');
    const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');
    const limitMessage = document.getElementById(prefix + '_kasbon_limit_message');

    if (!amountInput || !maxKasbonData[prefix]) {
        const amountValue = parseCurrencyInput(amountInput ? amountInput.value : '');
        if (amountValue >= 1000) {
            disableSubmitButton(prefix, false);
        }
        return;
    }

    const amount = parseCurrencyInput(amountInput.value);
    const maxKasbon = maxKasbonData[prefix].max_kasbon;

    if (amount > maxKasbon) {
        disableSubmitButton(prefix, true);

        if (limitAlert && limitMessage) {
            limitMessage.textContent =
                `Jumlah kasbon melebihi batas maksimal ${maxKasbonData[prefix].max_kasbon_formatted}`;
            setAlertStyle(limitAlert, 'error');
        }
    } else if (amount >= 1000) {
        disableSubmitButton(prefix, false);

        if (limitAlert && limitMessage) {
            limitMessage.textContent = maxKasbonData[prefix].message;
            setAlertStyle(limitAlert, 'warning');
        }
    } else {
        if (amount > 0) {
            disableSubmitButton(prefix, true);
        }
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
 * Menginisialisasi checkbox pilih semua dan pendengar checkbox individu.
 * Memperbarui status tombol hapus berdasarkan pilihan.
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
 * Memperbarui status tombol hapus berdasarkan pilihan checkbox.
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
 * Menginisialisasi penanganan pengiriman formulir untuk modal tambah dan edit.
 * Menangani status memuat melalui handleFormSubmit() dari modul bersama.
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
 */
function initAmountFormatting() {
    document.querySelectorAll('.kasbon-amount-input').forEach(input => {
        if (input.value) {
            window.formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            window.formatCurrencyInput(this);
            const prefix = this.id === 'add_amount' ? 'add' :
                `edit_${this.closest('[id^="editModal"]')?.id.replace('editModal', '') || ''}`;
            validateKasbonAmount(prefix);
        });
    });
}

// ==========================================
// HELPER UI
// ==========================================

/**
 * Mengatur gaya peringatan (warning/error) untuk elemen peringatan batas.
 *
 * @param {HTMLElement} alertEl  Element container peringatan
 * @param {string}      style    'warning' atau 'error'
 */
function setAlertStyle(alertEl, style) {
    alertEl.classList.remove('hidden');

    const textDiv = alertEl.querySelector('.text-sm');
    const icon = alertEl.querySelector('i');

    if (style === 'error') {
        alertEl.classList.remove('bg-warning-light', 'border-border-strong');
        alertEl.classList.add('bg-error-light', 'border-error');
        if (icon) {
            icon.classList.remove('text-warning');
            icon.classList.add('text-error');
        }
        if (textDiv) {
            textDiv.classList.remove('text-warning');
            textDiv.classList.add('text-error');
        }
    } else {
        alertEl.classList.remove('bg-error-light', 'border-error');
        alertEl.classList.add('bg-warning-light', 'border-border-strong');
        if (icon) {
            icon.classList.remove('text-error');
            icon.classList.add('text-warning');
        }
        if (textDiv) {
            textDiv.classList.remove('text-error');
            textDiv.classList.add('text-warning');
        }
    }
}

/**
 * Mengaktifkan atau menonaktifkan tombol kirim untuk awalan formulir tertentu.
 *
 * @param {string}  prefix   Awalan formulir ('add' atau 'edit_KSB001')
 * @param {boolean} disable  true untuk menonaktifkan, false untuk mengaktifkan
 */
function disableSubmitButton(prefix, disable) {
    let modalId;
    if (prefix === 'add') {
        modalId = 'addModal';
    } else if (prefix.startsWith('edit_')) {
        modalId = 'editModal' + prefix.replace('edit_', '');
    }

    const submitButton = modalId ? document.getElementById('submit-btn-' + modalId) : null;
    if (submitButton) {
        submitButton.disabled = disable;
        if (disable) {
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

/**
 * Mendapatkan URL untuk rute bernama dari atribut data.
 * Fallback ke pembacaan dari tag meta atau konfigurasi window.
 *
 * @param  {string} routeName  Nama rute Laravel
 * @returns {string} URL rute
 */
function pageUrl(routeName) {
    const urlMap = {
        'kasbon.check-max': pageContainer?.dataset.urlCheckMax || '/kasbon/check-max',
        'payroll.get-weeks': pageContainer?.dataset.urlGetWeeks || '/payroll/weeks',
    };
    return urlMap[routeName] || '#';
}

// ==========================================
// INISIALISASI
// ==========================================

/**
 * Menginisialisasi semua fungsionalitas halaman kasbon saat DOM siap.
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
