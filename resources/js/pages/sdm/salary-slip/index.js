/**
 * Halaman Indeks Slip Gaji - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Slip Gaji:
 * - Grid rekap absensi pada modal Edit (toggle H/I/S/C/A/L) + ringkasan live
 * - Pemuatan dinamis daftar karyawan bulanan pada modal Generate (sesuai periode)
 * - Grid centang Hari Libur pada modal Generate (renderHolidayDays)
 * - Checkbox Pilih Semua & aksi massal (hapus, bayar)
 * - Handler submit form Generate/Edit dengan pencegahan double submit
 *
 * Data server dikirim lewat window.salarySlipConfig (di-set di
 * pages/sdm/salary-slips.blade.php). Fungsi yang dipanggil dari atribut HTML
 * inline diekspos ke window karena Vite memuat JS sebagai ES module.
 */

/**
 * Konfigurasi halaman slip gaji dari backend.
 * @type {Object<string, *>}
 */
const config = window.salarySlipConfig || {};

// ==========================================
// GRID ABSENSI 30 HARI (Modal Edit)
// ==========================================

/** Urutan status yang berputar saat tombol hari diklik. */
const STATUS_ORDER = ['H', 'I', 'S', 'C', 'A', 'L'];

/** Kelas Tailwind per status (warna tombol hari pada grid). */
const STATUS_CLASSES = {
    H: ['bg-success-light', 'text-success', 'border-success'],
    I: ['bg-warning-light', 'text-warning', 'border-warning'],
    S: ['bg-error-light', 'text-error', 'border-error'],
    C: ['bg-purple-100', 'text-purple-700', 'border-purple-300'],
    A: ['bg-surface-hover', 'text-text-label', 'border-border-strong'],
    L: ['bg-primary-light', 'text-primary', 'border-primary'],
};

/** Semua kelas yang mungkin menempel pada tombol hari (untuk dibersihkan). */
const ALL_STATUS_CLASSES = Object.values(STATUS_CLASSES).flat()
    .concat(STATUS_ORDER.map(function (s) { return 'status-' + s; }));

/**
 * Memperbarui tampilan ringkasan perhitungan pada modal Edit.
 *
 * Membaca hidden input attendance[hari] yang ada di dalam modal, menghitung
 * jumlah H/I/S/C/A/L, lalu memperbarui elemen .recap-* serta menghitung
 * semua angka slip live:
 *   Penerimaan = gaji pokok + (transport × hadir) + (makan × hadir)
 *   Potongan   = BPJS Kes 1% × gaji pokok + JHT 2% × UMP + JPN 1% × UMP
 *                + PPh 21 (input manual) + kasbon pending
 *   THP        = Penerimaan − Potongan (min 0)
 *
 * Data dasar (base-salary, transport-rate, meal-rate, ump, pph21, kasbon)
 * dibaca dari elemen .slip-calc-data pada modal. PPh 21 selalu dibaca live
 * dari input .pph21-input (fallback ke nilai awal).
 *
 * @param {HTMLElement} modal Modal Edit yang sedang aktif.
 */
function updateRecapSummary(modal) {
    if (!modal) return;

    const calcData = modal.querySelector('.slip-calc-data');
    if (!calcData) return;

    const baseSalary = parseInt(calcData.dataset.baseSalary || '0', 10);
    const transportRate = parseInt(calcData.dataset.transportRate || '0', 10);
    const mealRate = parseInt(calcData.dataset.mealRate || '0', 10);
    const ump = parseInt(calcData.dataset.ump || '0', 10);
    const kasbon = parseInt(calcData.dataset.kasbon || '0', 10);

    let present = 0;
    let permission = 0;
    let sick = 0;
    let leave = 0;
    let absent = 0;
    let libur = 0;

    modal.querySelectorAll('input[name^="attendance["]').forEach(function (input) {
        switch (input.value) {
            case 'I': permission++; break;
            case 'S': sick++; break;
            case 'C': leave++; break;
            case 'A': absent++; break;
            case 'L': libur++; break;
            default: present++; break;
        }
    });

    const pph21Input = modal.querySelector('.pph21-input');
    let pph21 = parseInt(calcData.dataset.pph21 || '0', 10);
    if (pph21Input && pph21Input.value !== '') {
        pph21 = parseInt(pph21Input.value, 10) || 0;
    }

    const transportTotal = transportRate * present;
    const mealTotal = mealRate * present;
    const totalIncome = baseSalary + transportTotal + mealTotal;

    const bpjsKesehatan = Math.round(baseSalary * 0.01);
    const jht = Math.round(ump * 0.02);
    const jpn = Math.round(ump * 0.01);
    const totalDeduction = bpjsKesehatan + jht + jpn + pph21 + kasbon;
    const net = Math.max(0, totalIncome - totalDeduction);

    const setText = function (selector, text) {
        const el = modal.querySelector(selector);
        if (el) el.textContent = text;
    };

    setText('.recap-present', present);
    setText('.recap-permission', permission);
    setText('.recap-sick', sick);
    setText('.recap-leave', leave);
    setText('.recap-absent', absent);
    setText('.recap-libur', libur);
    setText('.recap-transport', formatIDR(transportTotal));
    setText('.recap-meal', formatIDR(mealTotal));
    setText('.recap-income', formatIDR(totalIncome));
    setText('.recap-bpjs', formatIDR(bpjsKesehatan));
    setText('.recap-jht', formatIDR(jht));
    setText('.recap-jpn', formatIDR(jpn));
    setText('.recap-pph21', formatIDR(pph21));
    setText('.recap-kasbon', formatIDR(kasbon));
    setText('.recap-total-deduction', formatIDR(totalDeduction));
    setText('.recap-net', formatIDR(net));
}

/**
 * Memformat angka menjadi format IDR ("1.250.000").
 * @param {number} value
 * @returns {string}
 */
function formatIDR(value) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
}

/**
 * Mengganti status satu hari pada grid absensi (H→I→S→C→A→L→H).
 *
 * @param {HTMLElement} btn Tombol hari (class .day-btn).
 */
function toggleDayStatus(btn) {
    if (!btn) return;

    const current = btn.dataset.status || 'H';
    const nextIndex = (STATUS_ORDER.indexOf(current) + 1) % STATUS_ORDER.length;
    const next = STATUS_ORDER[nextIndex];

    // Perbarui marker status
    btn.dataset.status = next;

    // Bersihkan semua kelas status lalu pasang kelas status baru
    ALL_STATUS_CLASSES.forEach(function (cls) { btn.classList.remove(cls); });
    (STATUS_CLASSES[next] || []).forEach(function (cls) { btn.classList.add(cls); });
    btn.classList.add('status-' + next);

    // Perbarui huruf & tooltip
    const letter = btn.querySelector('.day-letter');
    if (letter) letter.textContent = next;
    btn.title = 'Hari ' + btn.dataset.day + ' — ' + next;

    // Perbarui hidden input agar ikut terkirim
    const modal = btn.closest('.fixed'); // modal wrapper
    const hidden = modal ? modal.querySelector('input[name="attendance[' + btn.dataset.day + ']"]') : null;
    if (hidden) hidden.value = next;

    updateRecapSummary(modal);
}

/**
 * Mengikat event toggle pada seluruh grid absensi sebuah modal Edit.
 * @param {HTMLElement} modal Modal Edit (id editModal-*).
 */
function initAttendanceGrid(modal) {
    if (!modal) return;

    modal.querySelectorAll('.day-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            toggleDayStatus(this);
        });
    });

    // Perbarui ringkasan saat PPh 21 diubah manual
    const pph21Input = modal.querySelector('.pph21-input');
    if (pph21Input) {
        pph21Input.addEventListener('input', function () {
            updateRecapSummary(modal);
        });
    }

    updateRecapSummary(modal);
}

// ==========================================
// PEMUATAN DINAMIS KARYAWAN (Modal Generate)
// ==========================================

/**
 * Merender grid centang "Hari Libur" pada modal Generate sesuai bulan/tahun
 * yang dipilih. Hari Minggu otomatis tercentang (sudah pasti Libur); admin
 * bisa mencentang tanggal libur lainnya (libur nasional, cuti bersama, dll).
 *
 * Nilai terkirim sebagai holidays[] berformat Y-m-d, lalu dipakai service
 * untuk menandai "L" pada matriks absensi default saat generate.
 */
function renderHolidayDays() {
    const monthSelect = document.getElementById('period_month');
    const yearInput = document.getElementById('period_year');
    const grid = document.getElementById('holiday-days-grid');

    if (!monthSelect || !yearInput || !grid) return;

    const month = parseInt(monthSelect.value, 10);
    const year = parseInt(yearInput.value, 10);

    if (!month || !year) {
        grid.innerHTML = '<p class="text-xs text-text-secondary col-span-full">Pilih bulan dan tahun untuk menampilkan tanggal.</p>';
        return;
    }

    const daysInMonth = new Date(year, month, 0).getDate();
    const cells = [];

    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month - 1, day);
        const isSunday = date.getDay() === 0;
        const iso = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');

        cells.push(
            '<label class="holiday-day-btn flex flex-col items-center justify-center py-1.5 rounded-lg border cursor-pointer transition-colors duration-150 select-none ' +
            (isSunday
                ? 'border-primary bg-primary-light text-primary'
                : 'border-border bg-surface-base text-text-input hover:border-primary-light') + '">' +
            '<input type="checkbox" name="holidays[]" value="' + escapeAttr(iso) + '" ' +
            'class="w-3.5 h-3.5 accent-primary"' + (isSunday ? ' checked' : '') + '>' +
            '<span class="text-[10px] leading-none mt-0.5">' + day + '</span>' +
            (isSunday ? '<span class="text-[8px] leading-none font-semibold">Min</span>' : '') +
            '</label>'
        );
    }

    grid.innerHTML = cells.join('');
}

/**
 * Penghitung urutan permintaan pemuatan daftar karyawan (anti race condition).
 *
 * Saat periode diubah beruntun (mis. mengganti bulan lalu mengetik tahun),
 * beberapa fetch boleh berjalan bersamaan dan respons yang tiba lebih dulu
 * bisa berasal dari periode yang TIDAK lagi aktif. Penghitung ini memastikan
 * hanya respons dari permintaan TERAKHIR yang diterapkan ke dropdown.
 */
let employeeLoadSequence = 0;

/**
 * Memuat daftar karyawan bulanan yang belum punya slip untuk periode yang
 * dipilih pada modal Generate, lalu memperbarui multi-select searchable.
 *
 * Alur:
 * - Fetch POST ke config.eligibleEmployeesUrl dengan period_month & period_year.
 * - Render ulang daftar opsi (.searchable-multi-options) pada wrapper
 *   multi-select karyawan; pilihan yang sudah dipilih direset.
 * - Inisialisasi ulang komponen multi-select via initSearchableMultiSelects.
 */
async function loadEligibleEmployees() {
    const monthSelect = document.getElementById('period_month');
    const yearInput = document.getElementById('period_year');
    const wrapper = document.querySelector('#generateModal .searchable-multi-select-wrapper');

    if (!monthSelect || !yearInput || !wrapper) return;

    const month = monthSelect.value;
    const year = yearInput.value;

    if (!month || !year) return;

    const sequence = ++employeeLoadSequence;

    try {
        const response = await fetch(config.eligibleEmployeesUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                period_month: month,
                period_year: year
            })
        });

        const data = await response.json();

        // Respons dari permintaan lama (periode sudah berubah) diabaikan.
        if (sequence !== employeeLoadSequence) return;

        const optionsContainer = wrapper.querySelector('.searchable-multi-options');
        if (!optionsContainer) return;

        optionsContainer.innerHTML = (data.data || []).map(function (employee) {
            return '<div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light searchable-multi-option" ' +
                'data-value="' + escapeAttr(employee.value) + '" ' +
                'data-search="' + escapeAttr(String(employee.label).toLowerCase()) + '" ' +
                'data-label="' + escapeAttr(employee.label) + '">' +
                '<label class="flex items-center gap-2 cursor-pointer">' +
                '<input type="checkbox" value="' + escapeAttr(employee.value) + '" ' +
                'class="searchable-multi-checkbox w-4 h-4 accent-primary">' +
                '<span class="font-medium text-sm text-text-heading">' + escapeAttr(employee.label) + '</span>' +
                '</label></div>';
        }).join('');

        // Reset state komponen lalu inisialisasi ulang dengan opsi baru
        delete wrapper.dataset.multiSelectInitialized;
        const tags = wrapper.querySelector('.searchable-multi-tags');
        const hiddenInputs = wrapper.querySelector('.searchable-multi-hidden-inputs');
        if (tags) tags.innerHTML = '';
        if (hiddenInputs) hiddenInputs.innerHTML = '';

        if (typeof window.initSearchableMultiSelects === 'function') {
            window.initSearchableMultiSelects(wrapper);
        }
    } catch (error) {
        console.error('Error loading eligible employees:', error);
    }
}

/**
 * Mengganti karakter berbahaya agar aman disisipkan ke markup/atribut HTML.
 * @param {string} value
 * @returns {string}
 */
function escapeAttr(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ==========================================
// SELECT ALL CHECKBOX
// ==========================================

/**
 * Memperbarui status tombol Bayar & Hapus massal berdasarkan checkbox terpilih.
 * - Tombol Bayar hanya aktif bila minimal satu slip DRAFT dipilih.
 * - Tombol Hapus aktif bila minimal satu slip (status apa pun) dipilih.
 * - Item "Export Dipilih" pada dropdown Print tampil bila ada yang dipilih,
 *   dengan jumlah terpilih pada selectedCountText.
 */
function updateButtonStates() {
    const deleteButton = document.getElementById('delete-button');
    const bulkPayButton = document.getElementById('bulk-pay-button');
    const printSelectedItem = document.getElementById('printSelectedItem');
    const selectedCountText = document.getElementById('selectedCountText');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled):checked');
    const checkedDraft = document.querySelectorAll('input[name="ids[]"][data-status="draft"]:not(:disabled):checked');

    if (checkedCheckboxes.length > 0) {
        deleteButton.disabled = false;
        deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        deleteButton.disabled = true;
        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
    }

    if (checkedDraft.length > 0) {
        bulkPayButton.disabled = false;
        bulkPayButton.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        bulkPayButton.disabled = true;
        bulkPayButton.classList.add('opacity-50', 'cursor-not-allowed');
    }

    if (printSelectedItem) {
        if (checkedCheckboxes.length > 0) {
            printSelectedItem.classList.remove('hidden');
        } else {
            printSelectedItem.classList.add('hidden');
        }
    }

    if (selectedCountText) {
        selectedCountText.textContent = checkedCheckboxes.length;
    }
}

/**
 * Mencetak slip gaji terpilih sebagai PDF (satu slip per halaman).
 *
 * Alur:
 * 1. Ambil route cetak dari hidden input `salary-slip-print-selected-route`.
 * 2. Jika route kosong, hentikan proses.
 * 3. Delegasikan ke sharedPrintSelected(route, btn) yang mengumpulkan
 *    checkbox tercentang, mengirim via AJAX, dan mengunduh file PDF.
 *
 * @param {HTMLButtonElement} btn - Tombol yang diklik.
 * @returns {boolean} true jika proses dimulai; false jika route kosong.
 */
window.printSelected = function (btn) {
    const printRoute = document.getElementById('salary-slip-print-selected-route');
    const route = printRoute ? printRoute.value : '';

    if (!route) return false;

    return window.sharedPrintSelected(route, btn);
};

/**
 * Mengirim form hapus massal dengan status memuat.
 * Dipanggil dari onclick inline pada modal konfirmasi hapus.
 */
window.submitDeleteForm = function () {
    const checkedCheckboxes = document.querySelectorAll('.slip-checkbox:checked');
    const deleteForm = document.getElementById('deleteForm');

    if (checkedCheckboxes.length === 0) {
        return;
    }

    deleteForm.querySelectorAll('input[name="ids[]"]').forEach(function (input) {
        input.remove();
    });

    checkedCheckboxes.forEach(function (checkbox) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = checkbox.value;
        deleteForm.appendChild(input);
    });

    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    deleteForm.submit();
};

/**
 * Mengirim form bayar massal dengan status memuat.
 * Dipanggil dari onclick inline pada modal konfirmasi bayar.
 */
window.submitBulkPayForm = function () {
    const checkedCheckboxes = document.querySelectorAll('.slip-checkbox:checked');
    const bulkPayForm = document.getElementById('bulkPayForm');

    if (checkedCheckboxes.length === 0) {
        return;
    }

    bulkPayForm.querySelectorAll('input[name="ids[]"]').forEach(function (input) {
        input.remove();
    });
    const existingDate = bulkPayForm.querySelector('input[name="payment_date"]');
    if (existingDate) existingDate.remove();

    checkedCheckboxes.forEach(function (checkbox) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = checkbox.value;
        bulkPayForm.appendChild(input);
    });

    const dateInput = document.createElement('input');
    dateInput.type = 'hidden';
    dateInput.name = 'payment_date';
    dateInput.value = new Date().toISOString().split('T')[0];
    bulkPayForm.appendChild(dateInput);

    const bulkPayBtn = document.getElementById('confirm-btn-bulkPayModal');
    if (bulkPayBtn) {
        bulkPayBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
        bulkPayBtn.disabled = true;
        bulkPayBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    bulkPayForm.submit();
};

// ==========================================
// FORM SUBMIT HANDLERS
// ==========================================

/**
 * Menginisialisasi handler submit form modal Generate dan Edit dengan
 * pencegahan double submit (handleFormSubmit global).
 */
function initFormSubmitHandlers() {
    const generateForm = document.querySelector('#generateModal form');
    if (generateForm) {
        generateForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn, undefined, 'Memproses...')) {
                e.preventDefault();
                return false;
            }
        });
    }

    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn, undefined, 'Memproses...')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// INISIALISASI
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    // Grid absensi pada tiap modal Edit (draft)
    document.querySelectorAll('[id^="editModal-"]').forEach(initAttendanceGrid);

    // Muat daftar karyawan & grid hari libur saat bulan/tahun modal
    // Generate berubah
    const periodMonthSelect = document.getElementById('period_month');
    const periodYearInput = document.getElementById('period_year');

    if (periodMonthSelect) {
        periodMonthSelect.addEventListener('change', function () {
            loadEligibleEmployees();
            renderHolidayDays();
        });
    }
    if (periodYearInput) {
        periodYearInput.addEventListener('input', function () {
            loadEligibleEmployees();
            renderHolidayDays();
        });
    }

    // Reset modal Generate saat ditutup
    window.addEventListener('modalClosed', function (e) {
        if (e.detail === 'generateModal') {
            loadEligibleEmployees();
            renderHolidayDays();
        }
    });

    // Selaraskan daftar karyawan & grid hari libur dengan periode form saat
    // modal Generate dibuka (mencegah opsi periode lama masih tampil).
    window.addEventListener('modalOpened', function (e) {
        if (e.detail === 'generateModal') {
            loadEligibleEmployees();
            renderHolidayDays();
        }
    });

    // Grid hari libur awal + daftar karyawan pada modal Generate
    renderHolidayDays();
    loadEligibleEmployees();

    // Checkbox Pilih Semua
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = this.checked;
            }, this);
            updateButtonStates();
        });
    }

    // Checkbox individu
    document.querySelectorAll('input[name="ids[]"]:not(:disabled)').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
            const checked = document.querySelectorAll('input[name="ids[]"]:not(:disabled):checked');
            if (selectAll) {
                selectAll.checked = checkboxes.length === checked.length;
            }
            updateButtonStates();
        });
    });

    updateButtonStates();

    initFormSubmitHandlers();
});
