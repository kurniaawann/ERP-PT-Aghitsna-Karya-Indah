/**
 * Logika halaman Karyawan (Data Karyawan).
 *
 * Menangani:
 * - Checkbox Pilih Semua / Batalkan Pilih Semua
 * - Manajemen status checkbox individu dan tombol hapus
 * - Pengiriman formulir hapus massal dengan status memuat
 * - Format mata uang (upah harian, gaji pokok, transport, makan, UMP) pada input
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
// Toggle Jenis Karyawan (Harian / Bulanan)
// ==========================================

/**
 * Menampilkan/menyembunyikan kolom upah sesuai jenis karyawan pada baris
 * form massal modal Tambah.
 *
 * - harian  : tampil "Upah Per Hari" (wajib), sembunyikan "Gaji Pokok"
 *             serta tampil detail tukang (No. Telepon, Divisi, Proyek, Alamat).
 * - bulanan : tampil "Gaji Pokok / Bulan" (wajib), sembunyikan "Upah Per Hari"
 *             serta sembunyikan detail tukang (No. Telepon, Divisi, Proyek, Alamat).
 *
 * @param {HTMLElement} row  Baris karyawan (div.employee-row).
 * @param {string}      type 'harian' | 'bulanan'
 */
function toggleRowWageFields(row, type) {
    if (!row) return;

    const isBulanan = type === 'bulanan';
    const harianField = row.querySelector('.wage-field-harian');
    const bulananField = row.querySelector('.wage-field-bulanan');
    const dailyWage = row.querySelector('.daily-wage-input');
    const baseSalary = row.querySelector('.base-salary-input');

    if (harianField) harianField.classList.toggle('hidden', isBulanan);
    if (bulananField) bulananField.classList.toggle('hidden', !isBulanan);

    if (dailyWage) dailyWage.required = !isBulanan;
    if (baseSalary) baseSalary.required = isBulanan;

    row.querySelectorAll('.harian-extra-field').forEach(function (field) {
        field.classList.toggle('hidden', isBulanan);
    });
}

/**
 * Mengikat event toggle jenis karyawan pada satu baris form massal modal
 * Tambah (dropdown .employment-type-select).
 */
function bindEmploymentTypeToggle(row) {
    if (!row) return;

    const select = row.querySelector('.employment-type-select');
    if (!select) return;

    select.addEventListener('change', function () {
        toggleRowWageFields(row, this.value);
    });

    toggleRowWageFields(row, select.value);
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

    // Format mata uang gaji pokok bulanan untuk input pada baris ini
    row.querySelectorAll('.base-salary-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Format mata uang transport/makan/UMP bulanan untuk input pada baris ini
    row.querySelectorAll('.monthly-currency-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Toggle kolom upah sesuai jenis karyawan (harian / bulanan)
    bindEmploymentTypeToggle(row);

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
    const bulkProjectButton = document.getElementById('bulk-project-button');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

    if (checkedCheckboxes.length > 0) {
        if (deleteButton) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');
        }
        if (bulkProjectButton) {
            bulkProjectButton.disabled = false;
            bulkProjectButton.classList.remove('opacity-50', 'cursor-not-allowed');
            bulkProjectButton.classList.add('hover:bg-btn-edit-hover');
        }
    } else {
        if (deleteButton) {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');
        }
        if (bulkProjectButton) {
            bulkProjectButton.disabled = true;
            bulkProjectButton.classList.add('opacity-50', 'cursor-not-allowed');
            bulkProjectButton.classList.remove('hover:bg-btn-edit-hover');
        }
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
// Bulk Update Project (Ubah Proyek Massal)
// ==========================================

/**
 * Membuka modal ubah proyek massal dan menampilkan jumlah karyawan terpilih.
 */
function openBulkProjectModal() {
    const checked = document.querySelectorAll('input[name="ids[]"]:checked');
    const countEl = document.getElementById('bulkProjectCount');
    if (countEl) {
        countEl.textContent = String(checked.length);
    }

    // Reset isian modal setiap kali dibuka.
    const hidden = document.getElementById('bulkProjectHidden');
    const clearCheckbox = document.getElementById('clearProjectCheckbox');
    if (hidden) hidden.value = '';
    if (clearCheckbox) clearCheckbox.checked = false;

    const label = document.querySelector('#bulkProjectDropdown .project-dropdown-label');
    if (label) {
        label.textContent = '-- Pilih Proyek --';
    }

    openModal('bulkProjectModal');
}

/**
 * Mengirimkan form ubah proyek massal.
 *
 * Mengumpulkan kode karyawan dari checkbox yang dicentang, lalu menentukan
 * mode aksi:
 * - "Kosongkan proyek" dicentang → clear_project = 1 (proyek dikosongkan).
 * - Selain itu, proyek tujuan wajib diisi dari dropdown.
 */
function submitBulkProjectForm() {
    const checked = Array.from(document.querySelectorAll('input[name="ids[]"]:checked'))
        .map(function (cb) {
            return cb.value;
        });

    if (checked.length === 0) {
        showToast('Tidak ada karyawan yang dipilih.', 'error');
        return;
    }

    const clearCheckbox = document.getElementById('clearProjectCheckbox');
    const clearProject = clearCheckbox && clearCheckbox.checked;
    const hidden = document.getElementById('bulkProjectHidden');
    const projectName = hidden ? hidden.value : '';

    if (!clearProject && !projectName) {
        showToast('Pilih proyek tujuan atau centang "Kosongkan proyek".', 'error');
        return;
    }

    document.getElementById('bulkProjectIds').value = checked.join(',');
    document.getElementById('bulkClearProject').value = clearProject ? '1' : '0';
    document.getElementById('bulkProjectValue').value = clearProject ? '' : projectName;

    const confirmBtn = document.getElementById('confirm-btn-bulkProjectModal');
    if (confirmBtn) {
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
        confirmBtn.disabled = true;
        confirmBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    document.getElementById('bulkProjectForm').submit();
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
    window.openBulkProjectModal = openBulkProjectModal;
    window.submitBulkProjectForm = submitBulkProjectForm;

    // Toggle visibilitas dropdown proyek saat "Kosongkan proyek" dicentang.
    var clearProjectCheckbox = document.getElementById('clearProjectCheckbox');
    if (clearProjectCheckbox) {
        clearProjectCheckbox.addEventListener('change', function () {
            var dropdownWrap = document.getElementById('bulkProjectDropdown');
            if (dropdownWrap) {
                dropdownWrap.classList.toggle('opacity-40', this.checked);
                dropdownWrap.classList.toggle('pointer-events-none', this.checked);
            }
        });
    }

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

    // Toggle kolom upah pada modal Edit (satu karyawan) sesuai jenis karyawan.
    document.querySelectorAll('.edit-employment-type-select').forEach(function (select) {
        const code = select.dataset.employeeCode;

        select.addEventListener('change', function () {
            const isBulanan = this.value === 'bulanan';
            const harianField = document.querySelector('.edit-wage-field-harian-' + code);
            const bulananField = document.querySelector('.edit-wage-field-bulanan-' + code);
            const dailyWage = harianField ? harianField.querySelector('.daily-wage-input') : null;
            const baseSalary = bulananField ? bulananField.querySelector('.base-salary-input') : null;

            if (harianField) {
                harianField.style.display = isBulanan ? 'none' : 'block';
            }
            if (bulananField) {
                bulananField.style.display = isBulanan ? 'block' : 'none';
                bulananField.classList.toggle('hidden', !isBulanan);
            }
            if (dailyWage) dailyWage.required = !isBulanan;
            if (baseSalary) baseSalary.required = isBulanan;

            document.querySelectorAll('.edit-harian-extra-' + code).forEach(function (field) {
                field.style.display = isBulanan ? 'none' : 'block';
            });
        });
    });

    // Format Mata Uang Gaji Pokok (modal Edit)
    document.querySelectorAll('.base-salary-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Format Mata Uang Upah Harian
    document.querySelectorAll('.daily-wage-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Format Mata Uang Transport / Makan / UMP (modal Edit, karyawan bulanan)
    document.querySelectorAll('.monthly-currency-input').forEach(function (input) {
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
