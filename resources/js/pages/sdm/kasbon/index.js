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
 * Menambah/menghapus atribut required pada sebuah elemen.
 *
 * @param {string} elementId - ID elemen.
 * @param {boolean} required - true untuk menambah required, false untuk menghapus.
 */
function setRequired(elementId, required) {
    const el = document.getElementById(elementId);
    if (!el) return;

    if (required) {
        el.setAttribute('required', 'required');
    } else {
        el.removeAttribute('required');
    }
}

/**
 * Menambah/menghapus atribut required pada input wajib baris proyek kasbon tim.
 *
 * Hanya input yang bisa difokuskan (bukan type=hidden) yang disentuh. Atribut
 * required hanya boleh aktif saat baris proyek terlihat (tipe tim), karena
 * browser tetap memvalidasi field required di dalam kontainer display:none
 * dan memblokir submit tanpa menampilkan pesan apa pun.
 *
 * @param {boolean} required - true untuk menambah required, false untuk menghapus.
 */
function setProjectRowsRequired(required) {
    const container = document.getElementById('add_project_rows');
    if (!container) return;

    container.querySelectorAll(
        '.kasbon-project-row .searchable-select-input, .kasbon-project-row .kasbon-amount-input, .kasbon-project-row .kasbon-project-date'
    ).forEach((el) => {
        if (required) {
            el.setAttribute('required', 'required');
        } else {
            el.removeAttribute('required');
        }
    });
}

/**
 * Menampilkan/menyembunyikan field karyawan, detail kasbon, divisi, dan
 * baris proyek sesuai jenis kasbon.
 *
 * Alur:
 * - 'team': tampilkan field divisi + baris proyek (dinamis, satu record per
 *   proyek); sembunyikan karyawan, detail kasbon (jumlah/periode/catatan
 *   personal), dan alert batas.
 * - 'personal': tampilkan karyawan + detail kasbon; sembunyikan divisi dan
 *   baris proyek (direset agar data proyek tidak ikut terkirim).
 * - kosong: sembunyikan semuanya beserta alert batas.
 *
 * Atribut required selalu diselaraskan dengan tipe yang aktif agar tidak ada
 * field required yang tersembunyi di kontainer display:none — hal ini
 * memblokir submit di browser ("An invalid form control is not focusable").
 *
 * Ditugaskan ke window karena dipanggil dari atribut onchange inline.
 *
 * @param {string} prefix - Awalan id elemen ('add' atau 'edit_KSB001').
 */
window.toggleEmployeeSelect = function (prefix) {
    const kasbonTypeSelect = document.getElementById(prefix + '_kasbon_type');
    const employeeField = document.getElementById(prefix + '_employee_field');
    const detailField = document.getElementById(prefix + '_kasbon_detail_field');
    const divisionField = document.getElementById(prefix + '_division_field');
    const projectField = document.getElementById(prefix + '_project_field');
    const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');

    if (!kasbonTypeSelect || !employeeField || !divisionField) return;

    const isAdd = prefix === 'add';
    const isTeam = kasbonTypeSelect.value === 'team';
    const isPersonal = kasbonTypeSelect.value === 'personal';

    setRequired(prefix + '_employee_id', isPersonal);
    setRequired(prefix + '_amount', isPersonal);
    setRequired(prefix + '_kasbon_date', isPersonal);
    setRequired(prefix + '_division', isTeam);

    if (isTeam) {
        employeeField.style.display = 'none';
        if (detailField) detailField.style.display = 'none';
        divisionField.style.display = 'block';
        if (projectField) projectField.style.display = 'block';
        if (limitAlert) limitAlert.classList.add('hidden');

        if (isAdd) {
            projectRows.ensureRow();
            setProjectRowsRequired(true);
        } else {
            const projectInput = projectField.querySelector('.searchable-select-input');
            if (projectInput) projectInput.setAttribute('required', 'required');
        }

        if (typeof initSearchableSelects === 'function') {
            initSearchableSelects(divisionField);
        }
    } else if (isPersonal) {
        employeeField.style.display = 'block';
        if (detailField) detailField.style.display = 'block';
        divisionField.style.display = 'none';
        if (projectField) {
            projectField.style.display = 'none';
            if (isAdd && projectRows.container) {
                projectRows.empty();
            } else {
                clearSearchableMultiSelect(projectField);
                const projectInput = projectField.querySelector('.searchable-select-input');
                if (projectInput) projectInput.removeAttribute('required');
            }
        }
        if (limitAlert) limitAlert.classList.add('hidden');

        // empty() sudah menghapus semua baris; setProjectRowsRequired(false)
        // di sini hanya sebagai pengaman bila ada baris yang tersisa.
        if (isAdd) setProjectRowsRequired(false);

        if (typeof initSearchableSelects === 'function') {
            initSearchableSelects(employeeField);
        }
    } else {
        employeeField.style.display = 'none';
        if (detailField) detailField.style.display = 'none';
        divisionField.style.display = 'none';
        if (projectField) projectField.style.display = 'none';
        if (limitAlert) limitAlert.classList.add('hidden');

        if (isAdd) setProjectRowsRequired(false);
    }
};

/**
 * Mengosongkan pilihan searchable multi-select dalam sebuah field.
 *
 * Men-uncheck semua checkbox komponen (event change dipicu agar state
 * internal komponen ikut diperbarui) lalu menyalakan ulang event change
 * pada checkbox yang masih tercentang.
 *
 * @param {HTMLElement} field - Elemen field yang memuat komponen multi-select.
 */
function clearSearchableMultiSelect(field) {
    const wrapper = field.querySelector('.searchable-multi-select-wrapper');
    if (!wrapper) return;

    wrapper.querySelectorAll('.searchable-multi-checkbox').forEach(cb => {
        if (cb.checked) {
            cb.checked = false;
            cb.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}

// ==========================================
// BARIS PROYEK DINAMIS (Kasbon Tim)
// ==========================================

/**
 * Pengelola baris proyek pada form Tambah Kasbon tim.
 *
 * Setiap baris = satu proyek (dropdown + jumlah + periode + catatan) yang
 * akan disimpan sebagai record kasbon terpisah. Baris dikloning dari
 * <template id="add_project_row_template"> lalu indeks name di-reindex
 * (projects[0]..projects[n]).
 *
 * Metode:
 * - init(): pasang tombol "Tambah Proyek" + delegasi hapus, lalu buat baris
 *   pertama.
 * - addRow(): tambah satu baris baru di bawah, inisialisasi komponen
 *   searchable-select + format amount, lalu resolve periode baris tersebut.
 * - empty(): kosongkan SEMUA baris tanpa membuat baris baru (dipakai saat
 *   beralih ke kasbon personal agar input projects[] hilang dari DOM).
 * - ensureRow(): pastikan minimal satu baris ada (dipakai saat beralih
 *   kembali ke kasbon tim setelah empty()).
 * - reindex(): perbaiki indeks name/id serta tampilkan/sembunyikan tombol
 *   hapus (minimal satu baris selalu tersisa).
 */
const projectRows = {
    container: null,
    template: null,

    init() {
        this.container = document.getElementById('add_project_rows');
        this.template = document.getElementById('add_project_row_template');

        if (!this.container || !this.template) return;

        const addBtn = document.getElementById('add_project_row_btn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.addRow());
        }

        this.container.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-project-row');
            if (!btn) return;

            const rows = this.container.querySelectorAll('.kasbon-project-row');
            if (rows.length <= 1) return;

            btn.closest('.kasbon-project-row').remove();
            this.reindex();
        });

        this.addRow();
    },

    addRow() {
        if (!this.template) return;

        this.container.appendChild(this.template.content.cloneNode(true));
        this.reindex();

        if (typeof initSearchableSelects === 'function') {
            initSearchableSelects(this.container);
        }
        initAmountFormatting();

        const rows = this.container.querySelectorAll('.kasbon-project-row');
        const dateInput = rows.length > 0 ? rows[rows.length - 1].querySelector('.kasbon-project-date') : null;
        if (dateInput) {
            window.resolveProjectPeriod(dateInput);
        }
    },

    empty() {
        if (this.container) this.container.innerHTML = '';
    },

    /**
     * Memastikan minimal satu baris proyek ada di dalam container.
     *
     * Dipakai saat beralih ke kasbon tim setelah baris dibersihkan oleh
     * empty() (mis. sebelumnya memilih kasbon personal).
     */
    ensureRow() {
        if (!this.container || this.container.querySelector('.kasbon-project-row')) return;
        this.addRow();
    },

    reindex() {
        const rows = this.container.querySelectorAll('.kasbon-project-row');

        rows.forEach((row, i) => {
            row.querySelectorAll('[name^="projects["]').forEach((el) => {
                const name = el.getAttribute('name');
                if (name) {
                    el.setAttribute('name', name.replace(/projects\[\d+\]/, 'projects[' + i + ']'));
                }
            });

            row.querySelectorAll('[id^="add_project_select_"]').forEach((el) => {
                const suffix = el.id.endsWith('-input') ? '-input' : '';
                el.id = 'add_project_select_' + i + suffix;
            });

            const removeBtn = row.querySelector('.remove-project-row');
            if (removeBtn) {
                removeBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
            }
        });
    },
};

// ==========================================
// PENYELESAIAN TANGGAL PERIODE (AJAX)
// ==========================================

/**
 * Mengambil minggu payroll yang memuat tanggal tertentu.
 *
 * Alur:
 * 1. Bulan & tahun diambil dari tanggal (format YYYY-MM-DD).
 * 2. AJAX GET ke GET_WEEKS_URL (rute payroll.get-weeks) dengan query
 *    month & year.
 * 3. Iterasi minggu yang dikembalikan; minggu pertama yang rentang
 *    start_date..end_date mengandung tanggal menjadi hasil.
 * 4. Jika tidak ada yang cocok, gunakan minggu terakhir sebagai fallback.
 * 5. Kembalikan objek minggu { start_date, end_date, week_number };
 *    null saat input kosong / error.
 *
 * @param  {string} date - Tanggal (YYYY-MM-DD).
 * @returns {Promise<{start_date: string, end_date: string, week_number: number}|null>}
 */
async function fetchWeeksForDate(date) {
    if (!date) return null;

    const parts = date.split('-');
    const year = parts[0];
    const month = parts[1];
    if (!month || !year) return null;

    try {
        const response = await fetch(`${GET_WEEKS_URL}?month=${month}&year=${year}`);
        const data = await response.json();
        const weeks = data.weeks || [];

        for (const week of weeks) {
            if (date >= week.start_date && date <= week.end_date) {
                return week;
            }
        }

        return weeks.length > 0 ? weeks[weeks.length - 1] : null;
    } catch (error) {
        console.error('Error resolving period start date:', error);
        return null;
    }
}

/**
 * Menyelesaikan period_start_date/period_end_date + week_number dari
 * tanggal kasbon yang dipilih (kasbon personal / edit).
 *
 * @param  {string} prefix - Awalan id elemen ('add' atau 'edit_KSB001').
 * @returns {Promise<{start_date: string, end_date: string, week_number: number}|null>}
 */
async function resolvePeriodStartDate(prefix) {
    const kasbonDateInput = document.getElementById(prefix + '_kasbon_date');
    return fetchWeeksForDate(kasbonDateInput ? kasbonDateInput.value : '');
}

/**
 * Menyelesaikan periode pada satu baris proyek kasbon tim dari tanggal
 * kasbon baris tersebut, lalu mengisi hidden period_start_date/period_end_date.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onchange inline
 * pada template baris proyek.
 *
 * @param {HTMLInputElement} input - Input tanggal (projects[i][kasbon_date]).
 */
window.resolveProjectPeriod = async function (input) {
    if (!input) return;

    const row = input.closest('.kasbon-project-row');
    if (!row) return;

    const week = await fetchWeeksForDate(input.value);
    if (!week) return;

    const periodStart = row.querySelector('.kasbon-project-period-start');
    const periodEnd = row.querySelector('.kasbon-project-period-end');
    if (periodStart) periodStart.value = week.start_date;
    if (periodEnd) periodEnd.value = week.end_date;
};

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

    projectRows.init();

    toggleEmployeeSelect('add');
    checkMaxKasbon('add');

    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }

    if (typeof window.initSearchableMultiSelects === 'function') {
        window.initSearchableMultiSelects();
    }
});
