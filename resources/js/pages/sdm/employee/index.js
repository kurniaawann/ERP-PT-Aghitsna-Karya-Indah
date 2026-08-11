/**
 * Logika halaman Karyawan (Data Karyawan).
 *
 * Menangani:
 * - Checkbox Pilih Semua / Batalkan Pilih Semua
 * - Manajemen status checkbox individu dan tombol hapus
 * - Pengiriman formulir hapus massal dengan status memuat
 * - Format mata uang upah harian pada input
 * - Baris dinamis pada modal Tambah (menambah banyak karyawan sekaligus)
 * - Penanganan pengiriman formulir Tambah/Edit dengan pencegahan pengiriman ganda
 *
 * Dropdown proyek (searchable + pagination 10 item) di modal Tambah/Edit dan
 * filter index memakai modul bersama components/project-dropdown.js.
 */

import { initAllProjectDropdowns } from '../../../components/project-dropdown.js';

// ==========================================
// Format Mata Uang untuk Input Upah Harian
// ==========================================

/**
 * Memformat nilai input field sebagai mata uang IDR (misalnya, 150000 -> "150.000").
 * Menghapus semua karakter non-digit dan memformat ulang.
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

// ==========================================
// Baris Dinamis Modal Tambah (Massal)
// ==========================================

/**
 * Mengambil semua baris karyawan yang sedang dirender di container.
 */
function getEmployeeRows(container) {
    return container ? container.querySelectorAll('.employee-row') : [];
}

/**
 * Memperbarui nomor urut tampilan tiap baris sesuai posisinya.
 */
function updateRowNumbers(container) {
    getEmployeeRows(container).forEach(function (row, index) {
        const numberEl = row.querySelector('.employee-row-number');
        if (numberEl) {
            numberEl.textContent = index + 1;
        }
    });
}

/**
 * Mengikat event pada satu baris karyawan.
 *
 * @param {HTMLElement} row            Baris yang diikat.
 * @param {boolean}     initComponents true = baris baru (inisialisasi ulang
 *                                     searchable select & dropdown proyek);
 *                                     false = baris awal yang sudah diinisialisasi.
 */
function bindEmployeeRowEvents(row, initComponents) {
    if (!row) return;

    // Tombol hapus baris
    row.querySelectorAll('.employee-remove-row').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const container = document.getElementById('employeesContainer');
            const rows = getEmployeeRows(container);

            if (rows.length <= 1) {
                // Jangan hapus baris terakhir; kosongkan saja isinya.
                row.querySelectorAll('input, textarea').forEach(function (el) {
                    if (el.type !== 'hidden') el.value = '';
                });
                return;
            }

            row.remove();
            updateRowNumbers(container);
        });
    });

    // Format mata uang upah harian untuk input pada baris ini
    row.querySelectorAll('.daily-wage-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Inisialisasi komponen baru pada baris yang baru ditambahkan.
    // Untuk baris awal komponen sudah diinisialisasi saat DOM siap.
    if (initComponents) {
        if (typeof window.initSearchableSelects === 'function') {
            window.initSearchableSelects(row);
        }
        initAllProjectDropdowns(row);
    }

    updateRowNumbers(document.getElementById('employeesContainer'));
}

/**
 * Menambahkan satu baris karyawan baru dari template dinamis.
 */
function addEmployeeRow() {
    const container = document.getElementById('employeesContainer');
    const template = document.getElementById('employeeRowTemplate');
    if (!container || !template) return;

    const nextIndex = container.querySelectorAll('.employee-row').length;
    const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;

    const row = wrapper.firstElementChild;
    if (!row) return;

    container.appendChild(row);
    bindEmployeeRowEvents(row, true);
}

// ==========================================
// Pilih Semua / Checkbox Individu
// ==========================================

/**
 * Mengaktifkan atau menonaktifkan tombol hapus berdasarkan jumlah checkbox yang dipilih.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

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
 * Mengirim formulir hapus massal dengan spinner memuat pada tombol konfirmasi.
 */
function submitDeleteForm() {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
}

// ==========================================
// Pendengar Event
// ==========================================

/**
 * Menginisialisasi seluruh fungsionalitas halaman karyawan saat DOM siap.
 *
 * Alur inisialisasi:
 * - Ekspos submitDeleteForm ke window (dipanggil dari onclick inline).
 * - Checkbox "Pilih Semua": centang/batalkan semua checkbox baris.
 * - Checkbox baris: perbarui status Pilih Semua dan tombol hapus.
 * - Format mata uang pada semua input .daily-wage-input (nilai awal + tiap input).
 * - Daftarkan handler submit form Tambah/Edit dengan pencegahan double submit.
 */
document.addEventListener('DOMContentLoaded', function () {
    window.submitDeleteForm = submitDeleteForm;

    // Checkbox Pilih Semua
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = this.checked;
            }, this);
            updateDeleteButtonState();
        });
    }

    // Checkbox Individu
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            var selectAllCheckbox = document.getElementById('selectAll');

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
            }
            updateDeleteButtonState();
        });
    });

    // Inisialisasi status tombol hapus
    updateDeleteButtonState();

    // Inisialisasi searchable selects (dari global bersama)
    if (typeof window.initSearchableSelects === 'function') {
        window.initSearchableSelects();
    }

    // Inisialisasi dropdown proyek (searchable + pagination) di modal
    // Tambah/Edit dan filter index via modul bersama.
    initAllProjectDropdowns();

    // Baris dinamis modal Tambah: ikat event baris awal (komponen sudah
    // diinisialisasi di atas) dan daftarkan tombol tambah baris.
    const employeesContainer = document.getElementById('employeesContainer');
    getEmployeeRows(employeesContainer).forEach(function (row) {
        bindEmployeeRowEvents(row, false);
    });

    const addEmployeeRowBtn = document.getElementById('addEmployeeRowBtn');
    if (addEmployeeRowBtn) {
        addEmployeeRowBtn.addEventListener('click', addEmployeeRow);
    }

    // Format Mata Uang Upah Harian
    document.querySelectorAll('.daily-wage-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Submit Form Tambah (dengan pencegahan pengiriman ganda)
    var addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn)) {
                e.preventDefault();
            }
        });
    }

    // Submit Form Edit (dengan pencegahan pengiriman ganda)
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn)) {
                e.preventDefault();
            }
        });
    });
});
