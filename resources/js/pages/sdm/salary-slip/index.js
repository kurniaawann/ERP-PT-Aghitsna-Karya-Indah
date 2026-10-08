/**
 * Tab "Slip Gaji" (Halaman Data Payroll) - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Slip Gaji:
 * - Grid rekap absensi pada modal Edit (toggle H/I/S/C/A/L) + ringkasan live
 * - Pemuatan dinamis daftar karyawan kantor (bulanan) pada modal Generate (sesuai periode)
 * - Kalender centang Hari Libur pada modal Generate (renderHolidayDays,
 *   minggu dimulai Senin, kolom Minggu paling kanan & merah)
 * - Input cicilan kasbon per karyawan pada modal Generate
 *   (renderKasbonInstallments)
 * - Modal Generate 2 langkah (goToGenerateStep): langkah 2 = grid rekap
 *   absensi semua karyawan terpilih sekaligus (renderGenerateAttendanceGrid),
 *   tombol hari seperti modal Edit + Reset semua ke Hadir
 * - Input cicilan kasbon bulan ini pada modal Edit (ringkasan live)
 * - Checkbox Pilih Semua & aksi massal (hapus, bayar)
 * - Handler submit form Generate/Edit dengan pencegahan double submit
 *
 * Data server dikirim lewat window.salarySlipConfig (di-set di
 * pages/sdm/partials/salary-slip-content.blade.php — tab "Slip Gaji" pada
 * halaman Data Payroll). Fungsi yang dipanggil dari atribut HTML
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
 *   Penerimaan = gaji pokok + (transport × hadir) + (makan × hadir) + lembur
 *   Potongan   = BPJS Kes 1% × gaji pokok + JHT 2% × UMP + JPN 1% × UMP
 *                + PPh 21 (input manual) + cicilan kasbon bulan ini
 *   THP        = Penerimaan − Potongan (min 0)
 *
 * Data dasar (base-salary, transport-rate, meal-rate, ump, pph21, overtime,
 * kasbon-total, kasbon) dibaca dari elemen .slip-calc-data pada modal.
 * PPh 21 dan cicilan kasbon selalu dibaca live dari input .pph21-input dan
 * .kasbon-installment-input (cicilan dibatasi 0..total sisa kasbon).
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
    const overtime = parseInt(calcData.dataset.overtime || '0', 10);
    const kasbonTotal = parseInt(calcData.dataset.kasbonTotal || '0', 10);
    let kasbon = parseInt(calcData.dataset.kasbon || '0', 10);

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

    // Cicilan kasbon bulan ini: kosong = 0, dibatasi 0..total sisa kasbon
    const kasbonInput = modal.querySelector('.kasbon-installment-input');
    if (kasbonInput && !kasbonInput.disabled) {
        kasbon = parseInt(kasbonInput.value, 10) || 0;
    }
    kasbon = Math.min(Math.max(0, kasbon), kasbonTotal);
    const kasbonRemaining = Math.max(0, kasbonTotal - kasbon);

    const transportTotal = transportRate * present;
    const mealTotal = mealRate * present;
    const totalIncome = baseSalary + transportTotal + mealTotal + overtime;

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
    setText('.recap-overtime', formatIDR(overtime));
    setText('.recap-income', formatIDR(totalIncome));
    setText('.recap-bpjs', formatIDR(bpjsKesehatan));
    setText('.recap-jht', formatIDR(jht));
    setText('.recap-jpn', formatIDR(jpn));
    setText('.recap-pph21', formatIDR(pph21));
    setText('.recap-kasbon', formatIDR(kasbon));

    const remainingInput = modal.querySelector('.recap-kasbon-remaining-input');
    if (remainingInput) remainingInput.value = formatIDR(kasbonRemaining);
    setText('.recap-total-deduction', formatIDR(totalDeduction));
    setText('.recap-net', formatIDR(net));
}

/**
 * Membatasi nilai input angka ke rentang atribut min..max (bila ada).
 * Nilai kosong dibiarkan (diperlakukan 0 oleh perhitungan).
 *
 * @param {HTMLInputElement} input
 */
function clampNumberInput(input) {
    if (!input || input.value === '') return;

    const min = input.min !== '' ? parseInt(input.min, 10) : null;
    const max = input.max !== '' ? parseInt(input.max, 10) : null;
    let value = parseInt(input.value, 10) || 0;

    if (min !== null && value < min) value = min;
    if (max !== null && value > max) value = max;

    input.value = value;
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

    // Cicilan kasbon: ringkasan live + batasi nilai ke 0..total sisa kasbon
    const kasbonInput = modal.querySelector('.kasbon-installment-input');
    if (kasbonInput) {
        kasbonInput.addEventListener('input', function () {
            updateRecapSummary(modal);
        });
        kasbonInput.addEventListener('change', function () {
            clampNumberInput(this);
            updateRecapSummary(modal);
        });
    }

    updateRecapSummary(modal);
}

// ==========================================
// PEMUATAN DINAMIS KARYAWAN (Modal Generate)
// ==========================================

/** Nama kolom kalender Hari Libur (minggu dimulai Senin, Minggu paling kanan). */
const CALENDAR_DAY_NAMES = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

/** Kelas sel tanggal kalender Hari Libur per kondisi. */
const HOLIDAY_CELL_CLASSES = {
    sunday: ['border-error', 'bg-error-light', 'text-error'],
    checked: ['border-primary', 'bg-primary-light', 'text-primary'],
    normal: ['border-border', 'bg-surface-base', 'text-text-input'],
};

/**
 * Menyelaraskan warna sel tanggal kalender Hari Libur dengan status
 * centangnya. Sel hari Minggu selalu merah; tanggal lain yang dicentang
 * ditandai warna primer.
 *
 * @param {HTMLElement} cell Elemen label .holiday-day-btn.
 */
function syncHolidayCell(cell) {
    if (!cell) return;

    const checkbox = cell.querySelector('input[type="checkbox"]');
    const isSunday = cell.dataset.sunday === '1';
    const state = isSunday ? 'sunday' : (checkbox && checkbox.checked ? 'checked' : 'normal');

    Object.values(HOLIDAY_CELL_CLASSES).flat().forEach(function (cls) { cell.classList.remove(cls); });
    HOLIDAY_CELL_CLASSES[state].forEach(function (cls) { cell.classList.add(cls); });
}

/**
 * Merender kalender centang "Hari Libur" pada modal Generate sesuai
 * bulan/tahun yang dipilih.
 *
 * - Tampilan kalender 7 kolom, minggu dimulai hari Senin sehingga kolom
 *   Minggu berada paling kanan; tanggal 1 diberi offset sel kosong sesuai
 *   harinya.
 * - Setiap hari Minggu berwarna merah dan otomatis tercentang (pasti Libur);
 *   admin bisa mencentang tanggal libur lain (libur nasional, cuti bersama).
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
        grid.innerHTML = '<p class="text-xs text-text-secondary">Pilih bulan dan tahun untuk menampilkan kalender.</p>';
        return;
    }

    const daysInMonth = new Date(year, month, 0).getDate();
    // getDay(): 0 = Minggu … 6 = Sabtu → offset kolom kalender Senin-pertama.
    const leadingBlanks = (new Date(year, month - 1, 1).getDay() + 6) % 7;
    const cells = [];

    CALENDAR_DAY_NAMES.forEach(function (name, index) {
        const isSundayColumn = index === 6;
        cells.push(
            '<div class="text-center text-[11px] font-semibold py-1 ' +
            (isSundayColumn ? 'text-error' : 'text-text-secondary') + '">' + name + '</div>'
        );
    });

    for (let blank = 0; blank < leadingBlanks; blank++) {
        cells.push('<div></div>');
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month - 1, day);
        const isSunday = date.getDay() === 0;
        const iso = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        const stateClasses = HOLIDAY_CELL_CLASSES[isSunday ? 'sunday' : 'normal'].join(' ');

        cells.push(
            '<label class="holiday-day-btn flex flex-col items-center justify-center gap-0.5 py-1.5 rounded-lg border cursor-pointer transition-colors duration-150 select-none ' +
            stateClasses + '" data-sunday="' + (isSunday ? '1' : '0') + '" title="' + escapeAttr(iso) + '">' +
            '<input type="checkbox" name="holidays[]" value="' + escapeAttr(iso) + '" ' +
            'class="w-3.5 h-3.5 accent-primary"' + (isSunday ? ' checked' : '') + '>' +
            '<span class="text-xs leading-none font-semibold">' + day + '</span>' +
            '</label>'
        );
    }

    grid.innerHTML = '<div class="grid grid-cols-7 gap-1.5">' + cells.join('') + '</div>';

    grid.querySelectorAll('.holiday-day-btn').forEach(function (cell) {
        const checkbox = cell.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.addEventListener('change', function () {
                syncHolidayCell(cell);
            });
        }
    });
}

// ==========================================
// CICILAN KASBON (Modal Generate)
// ==========================================

/**
 * Sisa kasbon per karyawan eligible pada periode modal Generate.
 * Struktur: { 'EMP001': { label: 'Nama - EMP001', kasbonTotal: 500000 }, ... }
 * Diisi dari respons loadEligibleEmployees().
 *
 * @type {Object<string, {label: string, kasbonTotal: number}>}
 */
let eligibleKasbon = {};

/**
 * Merender daftar input cicilan kasbon bulan ini untuk karyawan terpilih
 * yang masih punya sisa kasbon (modal Generate).
 *
 * - Nilai default = seluruh sisa kasbon, maksimal = sisa kasbon.
 * - Nilai yang sudah diketik admin dipertahankan saat daftar dirender ulang.
 * - Terkirim sebagai kasbon_installments[kode karyawan].
 */
function renderKasbonInstallments() {
    const container = document.getElementById('generate-kasbon-installments');
    const wrapper = document.querySelector('#generateModal .searchable-multi-select-wrapper');

    if (!container || !wrapper) return;

    const previous = {};
    container.querySelectorAll('input[data-employee-code]').forEach(function (input) {
        previous[input.dataset.employeeCode] = input.value;
    });

    const selected = Array.from(wrapper.querySelectorAll('.searchable-multi-hidden-inputs input'))
        .map(function (input) { return input.value; });

    if (selected.length === 0) {
        container.innerHTML = '<p class="text-xs text-text-label">Pilih karyawan untuk melihat sisa kasbon.</p>';
        return;
    }

    const rows = selected
        .filter(function (code) { return eligibleKasbon[code] && eligibleKasbon[code].kasbonTotal > 0; })
        .map(function (code) {
            const info = eligibleKasbon[code];
            const value = Object.prototype.hasOwnProperty.call(previous, code) ? previous[code] : info.kasbonTotal;

            return '<div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center p-2 bg-surface-base border border-border rounded-lg">' +
                '<div class="text-sm font-medium text-text-primary">' + escapeAttr(info.label) + '</div>' +
                '<div class="text-xs text-text-secondary">Sisa kasbon: <strong class="text-error">' + formatIDR(info.kasbonTotal) + '</strong></div>' +
                '<div class="relative">' +
                '<span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-label text-sm">Rp</span>' +
                '<input type="number" name="kasbon_installments[' + escapeAttr(code) + ']" ' +
                'data-employee-code="' + escapeAttr(code) + '" min="0" max="' + info.kasbonTotal + '" step="1" ' +
                'value="' + escapeAttr(value) + '" ' +
                'class="generate-kasbon-installment w-full border border-border-strong rounded p-2 pl-9 bg-surface-base text-text-input" ' +
                'title="Cicilan kasbon bulan ini (maks ' + escapeAttr(formatIDR(info.kasbonTotal)) + ')">' +
                '</div>' +
                '</div>';
        });

    container.innerHTML = rows.length > 0
        ? rows.join('')
        : '<p class="text-xs text-text-label">Karyawan terpilih tidak memiliki sisa kasbon.</p>';

    container.querySelectorAll('.generate-kasbon-installment').forEach(function (input) {
        input.addEventListener('change', function () {
            clampNumberInput(this);
        });
    });
}

/**
 * Mengamati perubahan pilihan karyawan pada multi-select modal Generate
 * (hidden input employee_codes[] dirender ulang komponen) lalu merender
 * ulang daftar cicilan kasbon.
 */
function observeEmployeeSelection() {
    const hiddenInputs = document.querySelector('#generateModal .searchable-multi-hidden-inputs');

    if (!hiddenInputs || typeof MutationObserver === 'undefined') return;

    const observer = new MutationObserver(function () {
        renderKasbonInstallments();

        // Pilihan berubah → hapus pesan "pilih minimal satu karyawan" agar
        // tidak menahan submit form.
        const search = document.querySelector('#generateModal .searchable-multi-select-input');
        if (search) search.setCustomValidity('');
    });

    observer.observe(hiddenInputs, { childList: true });
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

        // Simpan sisa kasbon per karyawan untuk daftar cicilan kasbon.
        eligibleKasbon = {};
        (data.data || []).forEach(function (employee) {
            eligibleKasbon[employee.value] = {
                label: employee.label,
                kasbonTotal: parseInt(employee.kasbon_total || 0, 10) || 0,
            };
        });

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

        renderKasbonInstallments();
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
// LANGKAH 2: REKAP ABSENSI MASSAL (Modal Generate)
// ==========================================

/** Langkah aktif modal Generate (1 = Data Slip & Tanggal Merah, 2 = Rekap Absensi). */
let generateStep = 1;

/**
 * Status hari yang diubah admin dari default, per karyawan:
 * { 'EMP001': { 5: 'C' }, ... }. Disimpan terpisah dari default agar
 * perubahan tetap ada saat admin kembali ke langkah 1 (mis. menambah
 * tanggal merah atau karyawan) lalu Lanjut lagi.
 *
 * @type {Object<string, Object<number, string>>}
 */
let generateOverrides = {};

/** Periode ("tahun-bulan") pemilik generateOverrides; periode berubah → direset. */
let generateOverridesPeriod = '';

/** Default status per hari periode aktif (indeks 1..jumlah hari; L = Minggu/tanggal merah). */
let generateDefaultDays = [];

/** Nama hari singkat sesuai Date.getDay() (0 = Minggu). */
const WEEKDAY_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

/** Kelas penanda langkah aktif / tidak aktif pada header modal Generate. */
const STEP_INDICATOR_CLASSES = {
    active: ['font-semibold', 'text-primary'],
    inactive: ['text-text-secondary'],
};
const STEP_BADGE_CLASSES = {
    active: ['bg-primary', 'text-white'],
    done: ['bg-success', 'text-white'],
    inactive: ['bg-surface-hover', 'text-text-label'],
};

/**
 * Karyawan terpilih pada multi-select modal Generate (urutan sesuai pilihan).
 *
 * @returns {Array<{code: string, name: string}>}
 */
function getSelectedGenerateEmployees() {
    const wrapper = document.querySelector('#generateModal .searchable-multi-select-wrapper');
    if (!wrapper) return [];

    return Array.from(wrapper.querySelectorAll('.searchable-multi-hidden-inputs input')).map(function (input) {
        const code = input.value;
        const option = wrapper.querySelector('.searchable-multi-option[data-value="' + CSS.escape(code) + '"]');
        const label = (eligibleKasbon[code] && eligibleKasbon[code].label) || (option ? option.dataset.label : '') || code;
        const suffix = ' - ' + code;
        const name = label.endsWith(suffix) ? label.slice(0, -suffix.length) : label;

        return { code: code, name: name };
    });
}

/**
 * Status default tiap hari periode: Minggu dan tanggal merah yang dicentang
 * di langkah 1 = L (Libur), lainnya H (Hadir) — sama dengan
 * SalarySlipService::buildDefaultAttendance.
 *
 * @param {number} year
 * @param {number} month 1..12
 * @returns {Array<string>} Indeks 1..jumlah hari
 */
function buildGenerateDefaultDays(year, month) {
    const holidays = new Set();
    document.querySelectorAll('#holiday-days-grid input[name="holidays[]"]:checked').forEach(function (checkbox) {
        const day = parseInt(String(checkbox.value).split('-')[2], 10);
        if (day) holidays.add(day);
    });

    const daysInMonth = new Date(year, month, 0).getDate();
    const days = [null];
    for (let day = 1; day <= daysInMonth; day++) {
        const isSunday = new Date(year, month - 1, day).getDay() === 0;
        days.push(isSunday || holidays.has(day) ? 'L' : 'H');
    }

    return days;
}

/**
 * Status efektif satu karyawan pada satu hari (perubahan admin atau default).
 *
 * @param {string} code
 * @param {number} day
 * @returns {string}
 */
function getGenerateDayStatus(code, day) {
    const overrides = generateOverrides[code];
    return (overrides && overrides[day]) || generateDefaultDays[day] || 'H';
}

/**
 * Menyegarkan hidden input attendance[kode] ("HHLHC...") dan ringkasan
 * baris satu karyawan pada grid rekap absensi.
 *
 * @param {HTMLTableRowElement} row Baris karyawan (tr[data-code]).
 */
function syncGenerateAttendanceRow(row) {
    if (!row) return;

    const code = row.dataset.code;
    const counts = { H: 0, I: 0, S: 0, C: 0, A: 0, L: 0 };
    let letters = '';

    for (let day = 1; day < generateDefaultDays.length; day++) {
        const status = getGenerateDayStatus(code, day);
        letters += status;
        counts[status] = (counts[status] || 0) + 1;
    }

    const hidden = row.querySelector('input[type="hidden"]');
    if (hidden) hidden.value = letters;

    const summary = row.querySelector('.gen-row-summary');
    if (summary) {
        const parts = ['<span class="text-success font-semibold">H ' + counts.H + '</span>'];
        [['I', 'text-warning'], ['S', 'text-error'], ['C', 'text-purple-700'], ['A', 'text-text-label']].forEach(function (item) {
            if (counts[item[0]] > 0) {
                parts.push('<span class="' + item[1] + ' font-semibold">' + item[0] + ' ' + counts[item[0]] + '</span>');
            }
        });
        summary.innerHTML = parts.join(' · ');
    }
}

/**
 * Memasang warna & huruf status pada tombol hari grid rekap absensi
 * (warna sama dengan grid modal Edit / STATUS_CLASSES).
 *
 * @param {HTMLButtonElement} btn
 * @param {string} status
 */
function paintGenerateDayButton(btn, status) {
    btn.dataset.status = status;
    ALL_STATUS_CLASSES.forEach(function (cls) { btn.classList.remove(cls); });
    (STATUS_CLASSES[status] || []).forEach(function (cls) { btn.classList.add(cls); });
    btn.textContent = status;
    btn.title = 'Tanggal ' + btn.dataset.day + ' — ' + status;
}

/**
 * Merender grid rekap absensi langkah 2: baris = karyawan terpilih,
 * kolom = tanggal periode, sel = tombol status seperti modal Edit.
 *
 * - Header tanggal Minggu/tanggal merah berwarna merah dan default L.
 * - Perubahan admin (generateOverrides) dipertahankan selama periode sama.
 * - Setiap baris punya hidden input attendance[kode] = "HHLHC..." yang
 *   dikirim ke SalarySlipController@generate.
 */
function renderGenerateAttendanceGrid() {
    const container = document.getElementById('generate-attendance-grid');
    const monthSelect = document.getElementById('period_month');
    const yearInput = document.getElementById('period_year');
    if (!container || !monthSelect || !yearInput) return;

    const month = parseInt(monthSelect.value, 10);
    const year = parseInt(yearInput.value, 10);
    const periodKey = year + '-' + month;

    if (periodKey !== generateOverridesPeriod) {
        generateOverrides = {};
        generateOverridesPeriod = periodKey;
    }

    generateDefaultDays = buildGenerateDefaultDays(year, month);
    const employees = getSelectedGenerateEmployees();
    const monthLabel = monthSelect.options[monthSelect.selectedIndex]
        ? monthSelect.options[monthSelect.selectedIndex].text
        : '';

    let head = '<th class="sticky left-0 z-10 bg-surface-secondary px-3 py-2 text-left font-medium whitespace-nowrap">Karyawan</th>';
    for (let day = 1; day < generateDefaultDays.length; day++) {
        const isRed = generateDefaultDays[day] === 'L';
        head += '<th class="px-0.5 py-1.5 text-center font-medium ' + (isRed ? 'text-error' : 'text-text-secondary') + '">' +
            '<div class="leading-none">' + day + '</div>' +
            '<div class="text-[10px] font-normal leading-none mt-0.5">' + WEEKDAY_SHORT[new Date(year, month - 1, day).getDay()] + '</div>' +
            '</th>';
    }
    head += '<th class="px-3 py-2 text-left font-medium whitespace-nowrap">Rekap</th><th class="px-2"></th>';

    const rows = employees.map(function (employee) {
        let cells = '';
        for (let day = 1; day < generateDefaultDays.length; day++) {
            cells += '<td class="px-0.5 py-1">' +
                '<button type="button" class="gen-day-btn w-7 h-7 inline-flex items-center justify-center rounded border text-xs font-semibold transition-colors duration-150 select-none" ' +
                'data-day="' + day + '"></button></td>';
        }

        return '<tr class="border-t border-border" data-code="' + escapeAttr(employee.code) + '">' +
            '<td class="sticky left-0 z-10 bg-surface-base px-3 py-1.5 whitespace-nowrap">' +
            '<div class="font-medium text-text-primary">' + escapeAttr(employee.name) + '</div>' +
            '<div class="text-[11px] text-text-tertiary">' + escapeAttr(employee.code) + '</div>' +
            '<input type="hidden" name="attendance[' + escapeAttr(employee.code) + ']" value="">' +
            '</td>' +
            cells +
            '<td class="gen-row-summary px-3 py-1.5 whitespace-nowrap text-xs"></td>' +
            '<td class="px-2 py-1.5"><button type="button" class="gen-row-reset text-text-tertiary hover:text-primary" ' +
            'title="Reset baris ini ke Hadir"><i class="fa-solid fa-rotate-left"></i></button></td>' +
            '</tr>';
    }).join('');

    container.innerHTML =
        '<div class="rounded-lg border border-border">' +
        '<div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-surface-secondary rounded-t-lg">' +
        '<span class="text-sm font-medium text-text-primary">Rekap Absensi ' + escapeAttr(monthLabel + ' ' + year) +
        ' &middot; ' + employees.length + ' karyawan</span>' +
        '<button type="button" id="generate-reset-attendance" class="text-xs text-primary hover:underline">Reset semua ke Hadir</button>' +
        '</div>' +
        '<div class="overflow-x-auto">' +
        '<table class="min-w-full text-xs">' +
        '<thead><tr class="bg-surface-secondary">' + head + '</tr></thead>' +
        '<tbody>' + rows + '</tbody>' +
        '</table></div></div>';

    container.querySelectorAll('tr[data-code]').forEach(function (row) {
        row.querySelectorAll('.gen-day-btn').forEach(function (btn) {
            paintGenerateDayButton(btn, getGenerateDayStatus(row.dataset.code, parseInt(btn.dataset.day, 10)));
        });
        syncGenerateAttendanceRow(row);
    });
}

/**
 * Mengganti status satu hari pada grid rekap absensi.
 * Klik kiri maju (H → I → S → C → A → L → H), klik kanan mundur.
 *
 * @param {HTMLButtonElement} btn
 * @param {number} step +1 maju, -1 mundur
 */
function cycleGenerateDay(btn, step) {
    const row = btn.closest('tr[data-code]');
    if (!row) return;

    const code = row.dataset.code;
    const day = parseInt(btn.dataset.day, 10);
    const current = getGenerateDayStatus(code, day);
    const next = STATUS_ORDER[(STATUS_ORDER.indexOf(current) + step + STATUS_ORDER.length) % STATUS_ORDER.length];

    generateOverrides[code] = generateOverrides[code] || {};
    if (next === generateDefaultDays[day]) {
        delete generateOverrides[code][day];
    } else {
        generateOverrides[code][day] = next;
    }

    paintGenerateDayButton(btn, next);
    syncGenerateAttendanceRow(row);
}

/**
 * Mengembalikan baris karyawan (atau seluruh grid bila row null) ke status
 * default: Hadir, kecuali Minggu & tanggal merah tetap Libur.
 *
 * @param {HTMLTableRowElement|null} row
 */
function resetGenerateAttendance(row) {
    const rows = row ? [row] : Array.from(document.querySelectorAll('#generate-attendance-grid tr[data-code]'));

    rows.forEach(function (tr) {
        delete generateOverrides[tr.dataset.code];
        tr.querySelectorAll('.gen-day-btn').forEach(function (btn) {
            paintGenerateDayButton(btn, getGenerateDayStatus(tr.dataset.code, parseInt(btn.dataset.day, 10)));
        });
        syncGenerateAttendanceRow(tr);
    });
}

/**
 * Event delegation grid rekap absensi (tombol hari, reset baris, reset semua).
 */
function initGenerateAttendanceGrid() {
    const container = document.getElementById('generate-attendance-grid');
    if (!container) return;

    container.addEventListener('click', function (e) {
        const dayBtn = e.target.closest('.gen-day-btn');
        if (dayBtn) {
            cycleGenerateDay(dayBtn, 1);
            return;
        }

        const rowReset = e.target.closest('.gen-row-reset');
        if (rowReset) {
            resetGenerateAttendance(rowReset.closest('tr[data-code]'));
            return;
        }

        if (e.target.closest('#generate-reset-attendance')) {
            resetGenerateAttendance(null);
        }
    });

    container.addEventListener('contextmenu', function (e) {
        const dayBtn = e.target.closest('.gen-day-btn');
        if (dayBtn) {
            e.preventDefault();
            cycleGenerateDay(dayBtn, -1);
        }
    });
}

/**
 * Memeriksa isian langkah 1 sebelum lanjut ke rekap absensi: periode valid
 * dan minimal satu karyawan dipilih.
 *
 * @returns {boolean}
 */
function validateGenerateStepOne() {
    const monthSelect = document.getElementById('period_month');
    const yearInput = document.getElementById('period_year');

    if (monthSelect && !monthSelect.reportValidity()) return false;
    if (yearInput && !yearInput.reportValidity()) return false;

    const search = document.querySelector('#generateModal .searchable-multi-select-input');
    if (search) search.setCustomValidity('');

    if (getSelectedGenerateEmployees().length === 0) {
        if (search) {
            search.setCustomValidity('Pilih minimal satu karyawan kantor terlebih dahulu');
            search.reportValidity();
        }
        return false;
    }

    const invalidInstallment = Array.from(document.querySelectorAll('#generate-kasbon-installments input'))
        .find(function (input) { return !input.checkValidity(); });
    if (invalidInstallment) {
        invalidInstallment.reportValidity();
        return false;
    }

    return true;
}

/**
 * Pindah langkah modal Generate dengan tampilan bergeser.
 *
 * - Langkah 2: validasi langkah 1, render grid rekap absensi, modal
 *   dilebarkan (max-w-6xl) agar seluruh tanggal muat.
 * - Langkah 1: kembali ke isian awal (modal kembali max-w-lg).
 * Tombol footer: langkah 1 = Batal + Lanjut; langkah 2 = Batal + Kembali + Generate.
 *
 * @param {number} step 1 | 2
 * @param {{skipValidation?: boolean, instant?: boolean}} [options]
 * @returns {boolean} true bila langkah berpindah
 */
function goToGenerateStep(step, options) {
    const opts = options || {};
    const modal = document.getElementById('generateModal');
    if (!modal) return false;

    if (step === 2 && !opts.skipValidation && !validateGenerateStepOne()) {
        return false;
    }

    if (step === 2) {
        renderGenerateAttendanceGrid();
    }

    const forward = step > generateStep;
    generateStep = step;

    const panel = modal.firstElementChild;
    if (panel) {
        panel.classList.add('transition-[max-width]', 'duration-300');
        panel.classList.toggle('max-w-lg', step === 1);
        panel.classList.toggle('max-w-6xl', step === 2);
        panel.scrollTop = 0;
    }

    [1, 2].forEach(function (number) {
        const section = document.getElementById('generate-step-' + number);
        if (!section) return;

        if (number !== step) {
            section.classList.add('hidden');
            return;
        }

        section.classList.remove('hidden');
        if (opts.instant) return;

        // Animasi geser: mulai dari samping (kanan saat maju, kiri saat mundur)
        section.classList.add('opacity-0', forward ? 'translate-x-8' : '-translate-x-8');
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                section.classList.remove('opacity-0', 'translate-x-8', '-translate-x-8');
            });
        });
    });

    modal.querySelectorAll('.generate-step-indicator').forEach(function (indicator) {
        const number = parseInt(indicator.dataset.step, 10);
        const badge = indicator.querySelector('.generate-step-badge');
        const isActive = number === step;

        Object.values(STEP_INDICATOR_CLASSES).flat().forEach(function (cls) { indicator.classList.remove(cls); });
        STEP_INDICATOR_CLASSES[isActive ? 'active' : 'inactive'].forEach(function (cls) { indicator.classList.add(cls); });

        if (badge) {
            Object.values(STEP_BADGE_CLASSES).flat().forEach(function (cls) { badge.classList.remove(cls); });
            const state = isActive ? 'active' : (number < step ? 'done' : 'inactive');
            STEP_BADGE_CLASSES[state].forEach(function (cls) { badge.classList.add(cls); });
            badge.innerHTML = state === 'done' ? '<i class="fa-solid fa-check"></i>' : String(number);
        }
    });

    const submitBtn = document.getElementById('submit-btn-generateModal');
    const nextBtn = document.getElementById('generate-next-btn');
    const backBtn = document.getElementById('generate-back-btn');
    if (submitBtn) submitBtn.classList.toggle('hidden', step !== 2);
    if (nextBtn) nextBtn.classList.toggle('hidden', step !== 1);
    if (backBtn) backBtn.classList.toggle('hidden', step !== 2);

    return true;
}

/**
 * Menambahkan tombol Lanjut & Kembali pada footer modal Generate (sebelum
 * tombol Generate bawaan x-modal) lalu menampilkan langkah 1.
 */
function initGenerateSteps() {
    const submitBtn = document.getElementById('submit-btn-generateModal');
    if (!submitBtn) return;

    const backBtn = document.createElement('button');
    backBtn.type = 'button';
    backBtn.id = 'generate-back-btn';
    backBtn.className = 'hidden border border-border-strong text-text-primary px-4 py-2 rounded hover:bg-surface-secondary';
    backBtn.innerHTML = '<i class="fa-solid fa-arrow-left mr-1"></i> Kembali';
    backBtn.addEventListener('click', function () { goToGenerateStep(1); });

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.id = 'generate-next-btn';
    nextBtn.className = 'bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded';
    nextBtn.innerHTML = 'Lanjut: Rekap Absensi <i class="fa-solid fa-arrow-right ml-1"></i>';
    nextBtn.addEventListener('click', function () { goToGenerateStep(2); });

    submitBtn.parentNode.insertBefore(backBtn, submitBtn);
    submitBtn.parentNode.insertBefore(nextBtn, submitBtn);

    initGenerateAttendanceGrid();
    goToGenerateStep(1, { skipValidation: true, instant: true });
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
            // Langkah 1: Enter / submit berarti "Lanjut" ke rekap absensi.
            if (generateStep !== 2) {
                e.preventDefault();
                goToGenerateStep(2);
                return false;
            }

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

    // Reset modal Generate saat ditutup (kembali ke langkah 1, rekap
    // absensi yang belum di-generate dibuang)
    window.addEventListener('modalClosed', function (e) {
        if (e.detail === 'generateModal') {
            generateOverrides = {};
            generateOverridesPeriod = '';
            goToGenerateStep(1, { skipValidation: true, instant: true });
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

    // Daftar cicilan kasbon mengikuti pilihan karyawan
    observeEmployeeSelection();

    // Modal Generate 2 langkah (Lanjut → rekap absensi massal)
    initGenerateSteps();

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
