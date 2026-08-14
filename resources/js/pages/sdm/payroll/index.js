/**
 * Halaman Indeks Payroll - Modul JavaScript
 *
 * Menangani semua fungsionalitas interaktif untuk halaman Data Payroll:
 * - Pemuatan minggu dinamis (API get-weeks)
 * - Pengecekan absensi otomatis sebelum generate
 * - Dropdown filter minggu
 * - Checkbox Pilih Semua & aksi massal (hapus, bayar)
 * - Handler submit form Generate/Edit
 *  - Panel status kesiapan generate (absensi lengkap, belum ada payroll, proyek)
 *
 * Data server dikirim lewat window.payrollConfig (di-set di
 * pages/sdm/payroll.blade.php). Fungsi yang dipanggil dari atribut HTML
 * inline diekspos ke window karena Vite memuat JS sebagai ES module,
 * bukan global.
 */

import { initAllProjectDropdowns } from '../../../components/project-dropdown.js';

/**
 * Konfigurasi halaman payroll dari backend.
 *
 * Berisi URL get-weeks, URL cek absensi, token CSRF, dan nilai filter awal
 * (filterMonth/filterYear/filterWeek/currentYear) yang di-set Blade.
 *
 * @type {Object<string, *>}
 */
const config = window.payrollConfig || {};

// ==========================================
// DYNAMIC WEEK LOADING (Monday-Saturday weeks)
// ==========================================

/**
 * Mengambil daftar minggu (Senin-Sabtu) dari server untuk bulan/tahun tertentu.
 *
 * Alur:
 * - Validasi input bulan (1-12) dan tahun (≥ 2000); jika invalid → [].
 * - AJAX GET ke config.getWeeksUrl dengan query month & year.
 * - Mengembalikan array { week_number, label, start, end } (bersumber dari
 *   PayrollService::getWeeksInMonth); [] saat terjadi error.
 *
 * @param {string|number} month  Bulan (1-12).
 * @param {string|number} year   Tahun.
 * @returns {Promise<Array<{week_number: number, label: string, start: string, end: string}>>}
 */
async function fetchWeeks(month, year) {
    if (!month || !year || month < 1 || month > 12 || year < 2000) {
        return [];
    }

    try {
        const response = await fetch(`${config.getWeeksUrl}?month=${month}&year=${year}`);
        const data = await response.json();
        return data.weeks || [];
    } catch (error) {
        console.error('Error fetching weeks:', error);
        return [];
    }
}

/**
 * Mengisi elemen <select> dengan opsi minggu.
 *
 * Alur:
 * - Mulai dengan opsi placeholder "Pilih".
 * - Tambahkan satu <option> per minggu (nilai = week_number, teks = label).
 * - Pertahankan seleksi sebelumnya bila nilainya masih ada di daftar baru.
 * - Jika seleksi sebelumnya tidak lagi tersedia, kosongkan nilai select.
 *
 * @param {HTMLSelectElement} selectEl        Elemen select yang diisi.
 * @param {Array}             weeks           Daftar minggu hasil fetchWeeks.
 * @param {string|number}     [selectedValue] Nilai minggu yang sedang terpilih.
 */
function populateWeekDropdown(selectEl, weeks, selectedValue) {
    selectEl.innerHTML = '<option value="">Pilih</option>';

    weeks.forEach(function(week) {
        const option = document.createElement('option');
        option.value = week.week_number;
        option.textContent = week.label;

        if (selectedValue && parseInt(selectedValue) === week.week_number) {
            option.selected = true;
        }

        selectEl.appendChild(option);
    });

    // If previous selection no longer exists, clear it
    if (selectedValue && !weeks.some(w => w.week_number === parseInt(selectedValue))) {
        selectEl.value = '';
    }
}

// ==========================================
// AUTO-CHECK ATTENDANCE SAAT PILIH BULAN/TAHUN/MINGGU
// ==========================================

/**
 * Referensi elemen modal Generate yang di-cache saat DOM siap.
 *
 * Menghindari query DOM berulang pada setiap event; dipakai oleh
 * loadGenerateWeeks, updatePeriodDateInputs, dan checkAttendanceData.
 *
 * @type {HTMLElement|null}
 */
let periodMonthSelect = null;
let periodYearInput = null;
let weekNumberSelect = null;
let projectMultiWrapper = null;
let periodStartDateInput = null;
let periodEndDateInput = null;
let checkingLoader = null;
let allCompleteDiv = null;
let incompleteWarningDiv = null;
let completeInfoDiv = null;
let alreadyGeneratedWarningDiv = null;
let incompleteList = null;
let completeList = null;
let alreadyGeneratedList = null;
let noProjectWarningDiv = null;
let noProjectList = null;
let generateSubmitBtn = null;
let signatorySectionsContainer = null;
let additionalCostsSectionsContainer = null;

/**
 * Seleksi penanda tangan yang tersimpan secara persisten (keyboard:
 * "NAMA_PROYEK" + \u0000 + peran).
 *
 * Dipakai agar pilihan penanda tangan per proyek tidak hilang saat blok
 * dirender ulang (mis. user menambah/menghapus proyek lain setelah mengisi
 * proyek pertama). Dikosongkan saat modal Generate ditutup.
 *
 * @type {Map<string, {id: string, label: string}>}
 */
const signatorySelections = new Map();

/**
 * Timer debounce (setTimeout) untuk checkAttendanceData agar tidak membanjiri
 * server saat user cepat mengubah bulan/tahun/minggu.
 *
 * @type {number|null}
 */
let checkTimeout = null;

/**
 * Timer (setTimeout) untuk menunda kemunculan loader pengecekan absensi.
 *
 * Loader baru ditampilkan bila pengecekan berjalan lebih dari 250 ms, supaya
 * respons cepat (mis. menghapus satu tag proyek) tidak membuat tampilan
 * "berkedip" karena loader muncul lalu hilang seketika.
 *
 * @type {number|null}
 */
let loaderTimer = null;

/**
 * Cache daftar minggu dari loadGenerateWeeks; dipakai updatePeriodDateInputs
 * untuk mencari tanggal mulai/akhir dari minggu yang terpilih.
 *
 * @type {Array<{week_number: number, label: string, start: string, end: string}>}
 */
let cachedWeeksData = [];

/**
 * Memuat opsi minggu untuk modal Generate berdasarkan bulan/tahun terpilih.
 *
 * Alur:
 * - Bulan/tahun belum dipilih → reset dropdown (pesan placeholder), kosongkan
 *   cache dan hidden tanggal.
 * - Ambil minggu via fetchWeeks, simpan di cachedWeeksData, isi dropdown
 *   (mempertahankan minggu yang sedang terpilih), lalu sinkronkan tanggal
 *   periode via updatePeriodDateInputs().
 */
async function loadGenerateWeeks() {
    const month = periodMonthSelect.value;
    const year = periodYearInput.value;

    if (!month || !year) {
        weekNumberSelect.innerHTML = '<option value="">Pilih bulan & tahun terlebih dahulu</option>';
        cachedWeeksData = [];
        periodStartDateInput.value = '';
        periodEndDateInput.value = '';
        return;
    }

    const currentWeek = weekNumberSelect.value;
    const weeks = await fetchWeeks(month, year);
    cachedWeeksData = weeks;
    populateWeekDropdown(weekNumberSelect, weeks, currentWeek);
    updatePeriodDateInputs();
}

/**
 * Mengisi hidden input period_start_date/period_end_date sesuai minggu terpilih.
 *
 * Alur:
 * - Ambil week_number dari select; jika kosong → kosongkan kedua hidden.
 * - Cari minggu dengan week_number yang sama di cachedWeeksData.
 * - Ketemu → isi hidden dengan start_date dan end_date minggu tersebut;
 *   tidak ketemu → kosongkan keduanya.
 */
function updatePeriodDateInputs() {
    const selectedWeekNum = parseInt(weekNumberSelect.value);
    if (!selectedWeekNum) {
        periodStartDateInput.value = '';
        periodEndDateInput.value = '';
        return;
    }

    const selectedWeek = cachedWeeksData.find(w => w.week_number === selectedWeekNum);
    if (selectedWeek) {
        periodStartDateInput.value = selectedWeek.start_date;
        periodEndDateInput.value = selectedWeek.end_date;
    } else {
        periodStartDateInput.value = '';
        periodEndDateInput.value = '';
    }
}

/**
 * Mengambil daftar proyek yang terpilih pada multi-select proyek di modal
 * Generate (hidden input project_name[]).
 *
 * @returns {string[]}  Array nama proyek terpilih (kosong bila belum ada).
 */
function getSelectedProjects() {
    if (!projectMultiWrapper) return [];

    return Array.from(projectMultiWrapper.querySelectorAll('input[name="project_name[]"]'))
        .map(function (input) { return input.value; });
}

/**
 * Mereset multi-select proyek di modal Generate (kosongkan pilihan).
 *
 * Karena komponen searchable-multi-select menyimpan state internal (Map),
 * reset dilakukan dengan menghapus flag inisialisasi lalu menginisialisasi
 * ulang wrapper agar closure state yang lama dibuang.
 *
 * @returns {void}
 */
function resetProjectMultiSelect() {
    const wrapper = document.querySelector('#generateModal .searchable-multi-select-wrapper');
    if (!wrapper) return;

    delete wrapper.dataset.multiSelectInitialized;

    wrapper.querySelectorAll('.searchable-multi-checkbox').forEach(function (checkbox) {
        checkbox.checked = false;
    });

    const selectAll = wrapper.querySelector('.searchable-multi-select-all');
    if (selectAll) selectAll.checked = false;

    const tags = wrapper.querySelector('.searchable-multi-tags');
    if (tags) tags.innerHTML = '';

    const hiddenInputs = wrapper.querySelector('.searchable-multi-hidden-inputs');
    if (hiddenInputs) hiddenInputs.innerHTML = '';

    if (typeof window.initSearchableMultiSelects === 'function') {
        window.initSearchableMultiSelects(wrapper);
    }
}

/**
 * Mengganti karakter berbahaya agar aman disisipkan ke markup/atribut HTML.
 *
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

/**
 * Mendapatkan daftar petinggi dari konfigurasi halaman (opsi select penanda
 * tangan per proyek).
 *
 * @returns {Array<{id: number, name: string, position: string|null}>}
 */
function getExecutiveOptions() {
    return config.executives || [];
}

/**
 * Merender blok form penanda tangan (Disetujui/Diperiksa/Dibuat oleh) untuk
 * SETIAP proyek yang dipilih pada multi-select di modal Generate.
 *
 * Alur:
 * - Kosongkan container lalu render satu blok per proyek terpilih (setiap
 *   proyek bisa memiliki penanda tangan yang berbeda).
 * - Tiap blok memuat 3 searchable single-select; nilai terkirim sebagai
 *   signatories[NAMA_PROYEK][disetujui|diperiksa|dibuat] = ID petinggi.
 * - Setelah blok dirender, inisialisasi komponen searchable-select via
 *   window.initSearchableSelects(container).
 *
 * @returns {void}
 */
function renderSignatorySections() {
    if (!signatorySectionsContainer) return;

    const projects = getSelectedProjects();
    signatorySectionsContainer.innerHTML = '';

    if (projects.length === 0) {
        signatorySectionsContainer.innerHTML =
            '<p class="text-xs text-text-label italic">Pilih proyek terlebih dahulu untuk memilih penanda tangan.</p>';
        return;
    }

    const roleFields = [
        ['disetujui', 'Disetujui oleh'],
        ['diperiksa', 'Diperiksa oleh'],
        ['dibuat', 'Dibuat oleh'],
    ];

    projects.forEach(function (project) {
        const block = document.createElement('div');
        block.className = 'mb-4 p-3 bg-surface-base border border-border-strong rounded-lg';

        const projectAttr = escapeAttr(project);

        let blockHTML =
            '<p class="text-sm font-semibold text-text-primary mb-3">' +
            '<i class="fa-solid fa-folder-open text-text-label mr-1"></i>' +
            'Penanda Tangan - <span class="text-primary">' + projectAttr + '</span></p>';

        roleFields.forEach(function (role) {
            const roleKey = role[0];
            const roleLabel = role[1];
            const inputId = 'signatory-' + projectAttr + '-' + roleKey;
            const fieldName = 'signatories[' + projectAttr + '][' + roleKey + ']';

            // Pulihkan pilihan yang tersimpan (tidak hilang saat re-render)
            const saved = signatorySelections.get(project + '\u0000' + roleKey);

            blockHTML += '<div class="searchable-select-wrapper mb-3" data-select-id="' + inputId + '">';
            blockHTML += '<label class="block text-text-primary mb-1" for="' + inputId + '-input">' +
                roleLabel + ' <span class="text-xs text-text-label">(opsional)</span></label>';
            blockHTML += '<div class="relative">';
            blockHTML += '<input type="text" id="' + inputId + '-input" ' +
                'class="searchable-select-input w-full border rounded p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light" ' +
                'placeholder="Cari petinggi..." autocomplete="off" ' +
                'value="' + (saved ? escapeAttr(saved.label) : '') + '">';
            blockHTML += '<i class="fa-solid fa-chevron-down absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>';
            blockHTML += '<div class="searchable-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">';
            blockHTML += '<div class="searchable-options">';
            blockHTML += '<div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b border-border-light searchable-option" data-value="">' +
                '-- Pilih Cari petinggi... --</div>';

            getExecutiveOptions().forEach(function (exec) {
                const label = exec.name + (exec.position ? ' - ' + exec.position : '');
                blockHTML += '<div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light searchable-option" ' +
                    'data-value="' + exec.id + '" data-search="' + escapeAttr(label.toLowerCase()) + '" ' +
                    'data-label="' + escapeAttr(label) + '">' +
                    '<div class="font-medium text-text-heading">' + escapeAttr(label) + '</div></div>';
            });

            blockHTML += '</div>';
            blockHTML += '<div class="searchable-no-results p-4 text-center text-sm text-text-secondary hidden">' +
                '<i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>' +
                '<p>Tidak ada data ditemukan</p></div>';
            blockHTML += '</div></div>';
            blockHTML += '<input type="hidden" name="' + fieldName + '" class="searchable-select-hidden" value="' +
                (saved ? escapeAttr(saved.id) : '') + '">';
            blockHTML += '</div>';
        });

        block.innerHTML = blockHTML;
        signatorySectionsContainer.appendChild(block);
    });

    if (typeof window.initSearchableSelects === 'function') {
        window.initSearchableSelects(signatorySectionsContainer);
    }
}

// ==========================================
// BIAYA LAIN-LAIN (Per Proyek)
// ==========================================

/**
 * Merender blok form "Biaya Lain-lain" untuk SETIAP proyek yang dipilih pada
 * multi-select di modal Generate.
 *
 * Alur:
 * - Render inkremental: blok proyek yang masih dipilih dipertahankan (nilai
 *   input tidak hilang), blok proyek yang dihapus dihapus, dan blok baru
 *   ditambahkan untuk proyek yang baru dipilih.
 * - Tiap blok memuat daftar baris biaya: nama biaya + jumlah (Rp). Nilai
 *   terkirim sebagai additional_costs[NAMA_PROYEK][INDEX][name|amount].
 *
 * @returns {void}
 */
function renderAdditionalCostSections() {
    if (!additionalCostsSectionsContainer) return;

    const projects = getSelectedProjects();

    if (projects.length === 0) {
        additionalCostsSectionsContainer.innerHTML =
            '<p class="text-xs text-text-label italic">Pilih proyek terlebih dahulu untuk menambahkan biaya lain-lain.</p>';
        return;
    }

    const blocks = additionalCostsSectionsContainer.querySelectorAll('.additional-cost-block');
    const existing = new Map();
    blocks.forEach(function (block) {
        existing.set(block.dataset.project, block);
    });

    // Hapus blok untuk proyek yang tidak lagi dipilih
    existing.forEach(function (block, project) {
        if (!projects.includes(project)) block.remove();
    });

    // Tambahkan blok untuk proyek baru
    projects.forEach(function (project) {
        if (!existing.has(project)) {
            additionalCostsSectionsContainer.appendChild(createAdditionalCostBlock(project));
        }
    });
}

/**
 * Membuat blok form biaya lain-lain untuk satu proyek berisi satu baris
 * kosong pertama dan tombol "Tambah Biaya".
 *
 * @param {string} project  Nama proyek.
 * @returns {HTMLElement}
 */
function createAdditionalCostBlock(project) {
    const block = document.createElement('div');
    block.className = 'mb-4 p-3 bg-surface-base border border-border-strong rounded-lg additional-cost-block';
    block.dataset.project = project;

    const projectAttr = escapeAttr(project);

    block.innerHTML =
        '<p class="text-sm font-semibold text-text-primary mb-3">' +
        '<i class="fa-solid fa-folder-open text-text-label mr-1"></i>' +
        'Biaya Lain-lain - <span class="text-primary">' + projectAttr + '</span></p>' +
        '<div class="additional-cost-rows"></div>' +
        '<button type="button" class="additional-cost-add-btn mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary hover:text-primary-hover">' +
        '<i class="fa-solid fa-plus"></i> Tambah Biaya</button>';

    block.querySelector('.additional-cost-rows').appendChild(createAdditionalCostRow(project, 0));

    return block;
}

/**
 * Membuat satu baris input biaya lain-lain (nama + jumlah).
 *
 * @param {string} project  Nama proyek (dipakai di atribut name).
 * @param {number} index    Indeks baris.
 * @returns {HTMLElement}
 */
function createAdditionalCostRow(project, index) {
    const row = document.createElement('div');
    row.className = 'additional-cost-row flex gap-2 mb-2 items-center';

    const projectKey = escapeAttr(project);

    row.innerHTML =
        '<input type="text" name="additional_costs[' + projectKey + '][' + index + '][name]" ' +
        'placeholder="Nama biaya" class="w-1/2 border border-border-strong rounded p-2 bg-surface-base text-text-input text-sm">' +
        '<input type="text" name="additional_costs[' + projectKey + '][' + index + '][amount]" ' +
        'placeholder="Jumlah (Rp)" inputmode="numeric" ' +
        'class="w-1/3 border border-border-strong rounded p-2 bg-surface-base text-text-input text-sm additional-cost-amount">' +
        '<button type="button" class="additional-cost-remove-btn text-error hover:text-red-700 px-2">' +
        '<i class="fa-solid fa-trash"></i></button>';

    return row;
}

/**
 * Menambahkan satu baris biaya kosong baru pada akhir daftar baris blok.
 *
 * @param {HTMLElement} block  Blok proyek.
 * @returns {void}
 */
function addAdditionalCostRow(block) {
    const rows = block.querySelector('.additional-cost-rows');
    const project = block.dataset.project;
    const nextIndex = rows.querySelectorAll('.additional-cost-row').length;

    rows.appendChild(createAdditionalCostRow(project, nextIndex));
}

/**
 * Menyembunyikan semua panel status pengecekan absensi di modal Generate.
 *
 * Dipakai saat tidak ada proyek/periode valid, atau tepat sebelum merender
 * hasil baru — bukan di awal pengecekan — supaya panel lama tetap terlihat
 * selama menunggu respons (mencegah "kedip" layar).
 */
function hideAllStatusPanels() {
    allCompleteDiv.classList.add('hidden');
    incompleteWarningDiv.classList.add('hidden');
    completeInfoDiv.classList.add('hidden');
    alreadyGeneratedWarningDiv.classList.add('hidden');
    noProjectWarningDiv.classList.add('hidden');
}

/**
 * Membatalkan timer loader dan memastikan loader tersembunyi.
 *
 * Dipanggil pada semua jalur keluar checkAttendanceData agar loader yang
 * tertunda (belum 250 ms) tidak muncul setelah pengecekan selesai.
 */
function clearLoader() {
    clearTimeout(loaderTimer);
    loaderTimer = null;
    if (checkingLoader) {
        checkingLoader.classList.add('hidden');
    }
}

/**
 * Pengecekan kelengkapan data absensi untuk periode + daftar proyek yang
 * dipilih di modal Generate.
 *
 * Alur:
 * 1. Baca proyek terpilih, bulan, tahun, dan minggu dari modal.
 * 2. Proyek/periode belum valid → sembunyikan semua panel status dan
 *    nonaktifkan tombol generate.
 * 3. Proyek/periode valid → set hidden input tanggal mulai/akhir.
 * 4. Tampilkan loader hanya bila pengecekan berjalan > 250 ms (via timer),
 *    lalu POST period_start_date, period_end_date, dan project_name (array
 *    nama proyek) ke config.checkAttendanceUrl (dengan token CSRF) → hasil
 *    dari PayrollService::validateAttendanceCompleteness.
 * 5. Panel status lama disembunyikan HANYA setelah respons diterima, lalu
 *    render daftar karyawan lengkap (completeList) + info periode.
 * 6. Ada karyawan incomplete → tampilkan warning + daftar tanggal kosong
 *    (generate diblokir).
 * 7. Sudah digenerate tanpa karyawan baru → warning blokir; dengan karyawan
 *    baru → info bahwa generate boleh dilanjutkan untuk karyawan baru.
 * 8. Tidak ada karyawan baru & tidak ada yang digenerate → pesan tidak ada
 *    karyawan.
 * 9. can_generate = true → tampilkan panel lengkap & aktifkan tombol;
 *    selain itu nonaktifkan tombol dengan tooltip alasan (disableReason).
 * 10. Error → sembunyikan loader dan nonaktifkan tombol.
 */
async function checkAttendanceData() {
    const projects = getSelectedProjects();
    const month = periodMonthSelect.value;
    const year = periodYearInput.value;
    const weekNumber = weekNumberSelect.value;

    // Bersihkan loader yang mungkin tertunda dari pengecekan sebelumnya.
    clearLoader();

    if (projects.length === 0 || !month || !year || !weekNumber) {
        hideAllStatusPanels();
        if (generateSubmitBtn) {
            generateSubmitBtn.disabled = true;
            generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        periodStartDateInput.value = '';
        periodEndDateInput.value = '';
        return;
    }

    // Update hidden date inputs before checking
    updatePeriodDateInputs();

    const startDate = periodStartDateInput.value;
    const endDate = periodEndDateInput.value;

    if (!startDate || !endDate) {
        if (generateSubmitBtn) {
            generateSubmitBtn.disabled = true;
            generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        return;
    }

    // Tampilkan loader hanya bila pengecekan berjalan agak lama, supaya
    // perbaruan cepat tidak membuat layar "berkedip" (loader muncul-hilang).
    loaderTimer = setTimeout(function () {
        loaderTimer = null;
        checkingLoader.classList.remove('hidden');
    }, 250);

    try {
        const response = await fetch(config.checkAttendanceUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                period_start_date: startDate,
                period_end_date: endDate,
                project_name: projects
            })
        });

        const data = await response.json();

        // Hentikan timer & sembunyikan loader.
        clearLoader();

        // Panel status lama disembunyikan SEKARANG (data baru sudah diterima),
        // bukan di awal fungsi — sehingga panel tidak hilang-muncul saat
        // menunggu respons (penyebab tampilan "berkedip").
        hideAllStatusPanels();


        const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        // Gunakan can_generate dari backend
        let canGenerate = data.can_generate;
        let disableReason = '';

        // Tampilkan informasi periode (nama proyek terpilih tidak lagi
        // ditampilkan di header panel — proyek ditampilkan per karyawan).
        const periodInfo =
            `Periode: <strong>${data.period_start} - ${data.period_end}</strong> (${data.working_days} hari kerja)`;

        // Tampilkan karyawan dengan data lengkap (jika ada)
        if (data.complete_employees && data.complete_employees.length > 0) {
            completeInfoDiv.classList.remove('hidden');

            // Tambahkan info periode di bagian atas
            let completeHTML =
                `<p class="text-xs text-success mb-2 pb-2 border-b border-border-light">${periodInfo}</p>`;

            completeHTML += data.complete_employees.map(emp => {
                return `
                    <div class="flex items-center justify-between text-sm bg-surface-base p-2 rounded border border-success">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-success"></i>
                            <span class="font-medium text-text-heading">${emp.name}</span>
                            <span class="text-xs text-text-label">(${emp.employee_code})</span>
                            ${emp.project_name ? `<span class="text-xs bg-primary-light text-primary px-2 py-1 rounded">${emp.project_name}</span>` : ''}
                        </div>
                        <span class="text-xs text-success font-semibold">${emp.filled_days}/${emp.total_days} hari</span>
                    </div>
                `;
            }).join('');

            completeList.innerHTML = completeHTML;
        }

        // Check 1: If there are incomplete employees - CANNOT GENERATE
        if (data.incomplete_employees.length > 0) {
            disableReason = 'Data absensi belum lengkap';
            incompleteWarningDiv.classList.remove('hidden');

            // Tambahkan info periode di bagian atas
            let incompleteHTML =
                `<p class="text-xs text-error mb-2 pb-2 border-b border-error font-semibold">${periodInfo}</p>`;

            incompleteHTML += data.incomplete_employees.map(emp => {
                // Format tanggal yang kosong; sertakan nama hari & tandai Minggu
                // (kini dihitung sebagai hari kerja wajib) dengan warna error.
                const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const missingDatesFormatted = emp.missing_dates.map(date => {
                    const d = new Date(date);
                    const isSunday = d.getDay() === 0;
                    return `<strong class="${isSunday ? 'text-error underline' : ''}">${dayNames[d.getDay()]} ${d.getDate()}</strong>`;
                }).join(', ');

                return `
                    <div class="bg-surface-base p-2 rounded border border-error">
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-semibold text-text-heading text-sm">${emp.name}</span>
                            <div class="flex items-center gap-1 flex-wrap justify-end">
                                ${emp.project_name ? `<span class="text-xs bg-primary-light text-primary px-2 py-1 rounded">${emp.project_name}</span>` : ''}
                                <span class="text-xs bg-error-light text-error px-2 py-1 rounded">${emp.employee_code}</span>
                            </div>
                        </div>
                        <div class="text-xs text-text-label space-y-1">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-calendar-xmark text-error"></i>
                                <span><strong class="text-error">${emp.filled_days}</strong> dari <strong>${emp.total_days}</strong> hari kerja</span>
                            </div>
                            ${emp.missing_dates.length > 0 ? `
                            <div class="flex items-start gap-1">
                                <i class="fa-solid fa-ban text-error text-xs mt-0.5"></i>
                                <span>Tanggal kosong: <strong class="text-error">${missingDatesFormatted}</strong> ${monthNames[month]}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            incompleteList.innerHTML = incompleteHTML;
        }

        // Check 1b: Jika ada karyawan yang belum memiliki proyek - CANNOT GENERATE
        if (data.employees_without_project && data.employees_without_project.length > 0) {
            disableReason = 'Ada karyawan yang belum memiliki proyek';
            noProjectWarningDiv.classList.remove('hidden');
            noProjectList.innerHTML = data.employees_without_project.map(emp =>
                `<div class="flex items-center justify-between bg-surface-base p-2 rounded border border-warning">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-warning"></i>
                        <span class="font-medium text-text-heading">${emp.name}</span>
                        <span class="text-xs text-text-label">(${emp.employee_code})</span>
                    </div>
                </div>`
            ).join('');
        }

        // Check 2: If already generated AND no new employees - CANNOT GENERATE
        if (data.already_generated.length > 0 && !data.has_new_employees) {
            disableReason = 'Payroll sudah digenerate untuk semua karyawan';
            alreadyGeneratedWarningDiv.classList.remove('hidden');
            alreadyGeneratedList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
                data.already_generated.map(emp =>
                    `<li class="text-sm">${emp.name} <span class="text-xs text-warning">(${emp.employee_code})</span></li>`
                ).join('') + '</ul>';

            // Add additional message
            const noteDiv = document.createElement('div');
            noteDiv.className = 'mt-3 p-2 bg-warning-light rounded border border-border-strong';
            noteDiv.innerHTML =
                '<p class="text-xs text-warning"><strong>Catatan:</strong> Semua karyawan sudah memiliki payroll untuk periode ini. Tidak dapat melakukan generate ulang.</p>';
            alreadyGeneratedList.appendChild(noteDiv);
        }
        // If already generated BUT there are new employees - Show info
        else if (data.already_generated.length > 0 && data.has_new_employees) {
            // Show info about already generated, but allow generation for new employees
            alreadyGeneratedWarningDiv.classList.remove('hidden');
            alreadyGeneratedList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
                data.already_generated.map(emp =>
                    `<li class="text-sm">${emp.name} <span class="text-xs text-warning">(${emp.employee_code})</span></li>`
                ).join('') + '</ul>';

            // Add info message
            const noteDiv = document.createElement('div');
            noteDiv.className = 'mt-3 p-2 bg-success-light rounded border border-border-strong';
            noteDiv.innerHTML =
                '<p class="text-xs text-success"><strong>Info:</strong> Ada karyawan baru yang belum memiliki payroll. Anda dapat melanjutkan generate untuk karyawan baru tersebut.</p>';
            alreadyGeneratedList.appendChild(noteDiv);
        }

        // Check 3: Jika tidak ada karyawan yang perlu di-generate
        if (!data.has_new_employees && data.already_generated.length === 0) {
            disableReason = 'Tidak ada karyawan yang perlu di-generate untuk periode ini';
            incompleteWarningDiv.classList.remove('hidden');
            incompleteList.innerHTML = `
                <p class="text-xs text-error mb-2 pb-2 border-b border-error font-semibold">${periodInfo}</p>
                <div class="bg-surface-base p-3 rounded border border-error text-center">
                    <i class="fa-solid fa-users-slash text-error text-3xl mb-2"></i>
                    <p class="text-sm text-text-heading font-semibold">Tidak Ada Karyawan</p>
                    <p class="text-xs text-text-label mt-1">Tidak ada karyawan yang perlu di-generate payroll untuk periode ini.</p>
                </div>
            `;
        }
        // Check 4: If can generate (all conditions met) - SHOW SUCCESS
        if (canGenerate) {
            allCompleteDiv.classList.remove('hidden');
        }

        // Update button state
        if (generateSubmitBtn) {
            if (canGenerate) {
                generateSubmitBtn.disabled = false;
                generateSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                generateSubmitBtn.classList.add('hover:bg-success-hover');
            } else {
                generateSubmitBtn.disabled = true;
                generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                generateSubmitBtn.classList.remove('hover:bg-success-hover');
                generateSubmitBtn.title = disableReason;
            }
        }

    } catch (error) {
        console.error('Error:', error);
        clearLoader();
        // Disable button on error
        if (generateSubmitBtn) {
            generateSubmitBtn.disabled = true;
            generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
}

// ==========================================
// FILTER WEEK DROPDOWN (Index Page)
// ==========================================

/**
 * Referensi elemen dropdown filter minggu halaman index yang di-cache saat
 * DOM siap, dipakai oleh loadFilterWeeks.
 *
 * @type {HTMLElement|null}
 */
let filterMonthSelect = null;
let filterYearInput = null;
let filterWeekSelect = null;

/**
 * Memuat opsi minggu untuk dropdown filter halaman index.
 *
 * Alur:
 * - Bulan/tahun belum dipilih → dropdown berisi "Semua Minggu".
 * - Ambil minggu via fetchWeeks; tambahkan opsi per minggu.
 * - Tandai opsi yang sesuai filter aktif (filterWeek + filterMonth +
 *   filterYear) agar seleksi dipertahankan saat halaman dimuat.
 */
async function loadFilterWeeks() {
    if (!filterMonthSelect || !filterYearInput || !filterWeekSelect) return;

    const month = filterMonthSelect.value;
    const year = filterYearInput.value;

    if (!month || !year) {
        filterWeekSelect.innerHTML = '<option value="">Semua Minggu</option>';
        return;
    }

    const weeks = await fetchWeeks(month, year);
    filterWeekSelect.innerHTML = '<option value="">Semua Minggu</option>';

    weeks.forEach(function(week) {
        const option = document.createElement('option');
        option.value = week.week_number;
        option.textContent = week.label;

        if (config.filterWeek && parseInt(config.filterWeek) === week.week_number &&
            config.filterMonth == month && config.filterYear == year) {
            option.selected = true;
        }

        filterWeekSelect.appendChild(option);
    });
}

// ==========================================
// SELECT ALL CHECKBOX
// ==========================================

/**
 * Memperbarui status tombol Hapus & Bayar Massal berdasarkan checkbox terpilih.
 *
 * - Tombol Hapus diaktifkan bila minimal satu checkbox (status apa pun)
 *   tercentang — payroll draft maupun paid bisa dihapus.
 * - Tombol Bayar hanya diaktifkan bila minimal satu payroll DRAFT yang
 *   tercentang (payroll paid tidak bisa dibayar ulang).
 *
 * Saat tidak ada yang tercentang, keduanya dinonaktifkan dengan kelas
 * opacity-50 dan cursor-not-allowed.
 */
function updateButtonStates() {
    const deleteButton = document.getElementById('delete-button');
    const bulkPayButton = document.getElementById('bulk-pay-button');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled):checked');
    const checkedDraft = document.querySelectorAll('input[name="ids[]"][data-status="draft"]:not(:disabled):checked');

    if (checkedCheckboxes.length > 0) {
        // Enable Delete Button
        deleteButton.disabled = false;
        deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.add('hover:bg-btn-delete-hover');
    } else {
        // Disable Delete Button
        deleteButton.disabled = true;
        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.remove('hover:bg-btn-delete-hover');
    }

    if (checkedDraft.length > 0) {
        // Enable Bulk Pay Button
        bulkPayButton.disabled = false;
        bulkPayButton.classList.remove('opacity-50', 'cursor-not-allowed');
        bulkPayButton.classList.add('hover:bg-primary-hover');
    } else {
        // Disable Bulk Pay Button
        bulkPayButton.disabled = true;
        bulkPayButton.classList.add('opacity-50', 'cursor-not-allowed');
        bulkPayButton.classList.remove('hover:bg-primary-hover');
    }
}

/**
 * Sinkronkan status checkbox "pilih semua" pada setiap header grup proyek.
 *
 * Sebuah checkbox grup tercentang bila seluruh checkbox payroll di dalamnya
 * tercentang (dan ada minimal satu). Dipanggil setelah perubahan checkbox
 * individu / pilih-semua / pilih-grup.
 */
function syncGroupSelectStates() {
    document.querySelectorAll('.group-select-all').forEach(groupCheck => {
        const index = groupCheck.dataset.groupIndex;
        const selector = `input[name="ids[]"][data-group-index="${index}"]:not(:disabled)`;
        const checkboxes = document.querySelectorAll(selector);
        const checked = document.querySelectorAll(`${selector}:checked`);
        groupCheck.checked = checkboxes.length > 0 && checkboxes.length === checked.length;
    });
}

/**
 * Melakukan toggle tampilan baris karyawan dalam satu grup proyek.
 *
 * Dipanggil dari tombol collapse/expand pada header grup (onclick inline).
 * Baris karyawan diberi class payroll-group-rows-{index}; chevron dibalik
 * saat grup ditutup/dibuka.
 *
 * @param {number|string} index  Indeks grup pada halaman.
 */
window.togglePayrollGroup = function (index) {
    const rows = document.querySelectorAll(`.payroll-group-rows-${index}`);
    const chevron = document.querySelector(`.group-chevron-${index}`);

    rows.forEach(row => row.classList.toggle('hidden'));

    if (chevron) {
        chevron.classList.toggle('fa-chevron-down');
        chevron.classList.toggle('fa-chevron-up');
    }
};

/**
 * Menampilkan modal detail biaya lain-lain untuk sebuah grup proyek.
 *
 * Dipanggil dari badge jumlah di header grup (onclick inline). Data rincian
 * diambil dari atribut data-items (JSON) pada tombol badge, lalu dirender ke
 * modal shared #additionalCostDetailModal (judul, periode, baris nama+jumlah,
 * dan total).
 *
 * @param {HTMLElement} button  Elemen badge yang diklik.
 */
window.showAdditionalCostDetail = function (button) {
    const modal = document.getElementById('additionalCostDetailModal');
    if (!modal) return;

    const items = JSON.parse(button.dataset.items || '[]');
    const titleEl = document.getElementById('additional-cost-detail-title');
    const periodEl = document.getElementById('additional-cost-detail-period');
    const bodyEl = document.getElementById('additional-cost-detail-body');
    const totalEl = document.getElementById('additional-cost-detail-total');

    if (titleEl) titleEl.textContent = button.dataset.title || '';
    if (periodEl) periodEl.textContent = 'Periode: ' + (button.dataset.period || '');

    let total = 0;
    if (bodyEl) {
        bodyEl.innerHTML = '';
        if (items.length === 0) {
            bodyEl.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-text-secondary">Tidak ada biaya lain-lain.</td></tr>';
        } else {
            items.forEach(function (item, index) {
                const amount = Number(item.amount) || 0;
                total += amount;
                const tr = document.createElement('tr');
                tr.className = 'border-t border-border-light';
                tr.innerHTML =
                    '<td class="p-2">' + (index + 1) + '</td>' +
                    '<td class="p-2">' + (item.name || '-') + '</td>' +
                    '<td class="p-2 text-right">' + 'Rp ' + amount.toLocaleString('id-ID') + '</td>';
                bodyEl.appendChild(tr);
            });
        }
    }

    if (totalEl) totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');

    if (typeof openModal === 'function') {
        openModal('additionalCostDetailModal');
    }
};

/**
 * Mengirim form hapus massal dengan status memuat.
 *
 * Alur:
 * - Ambil checkbox payroll yang tercentang; jika kosong → batal.
 * - Bersihkan hidden input ids[] lama pada form, lalu buat ulang dari
 *   checkbox tercentang (nilai = ID payroll).
 * - Ganti isi tombol konfirmasi dengan spinner "Menghapus..." dan nonaktifkan.
 * - Submit form #deleteForm.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onclick inline pada
 * modal konfirmasi hapus (Vite memuat JS sebagai ES module, bukan global).
 */
window.submitDeleteForm = function () {
    const checkedCheckboxes = document.querySelectorAll('.payroll-checkbox:checked');
    const deleteForm = document.getElementById('deleteForm');

    if (checkedCheckboxes.length === 0) {
        return; // Don't submit if nothing is selected
    }

    // Remove previous inputs
    const existingIds = deleteForm.querySelectorAll('input[name="ids[]"]');
    existingIds.forEach(input => input.remove());

    // Add checked IDs to delete form
    checkedCheckboxes.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = checkbox.value;
        deleteForm.appendChild(input);
    });

    // Add loading state to delete button
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    // Submit form
    deleteForm.submit();
};

/**
 * Mengirim form bayar massal dengan status memuat.
 *
 * Alur:
 * - Ambil checkbox payroll yang tercentang; jika kosong → batal.
 * - Bersihkan hidden ids[] dan payment_date lama pada form.
 * - Buat ulang hidden ids[] dari checkbox tercentang.
 * - Tambahkan hidden payment_date berisi tanggal hari ini (Y-m-d) yang
 *   dipakai PayrollService::bulkPayPayrolls.
 * - Ganti isi tombol konfirmasi dengan spinner "Memproses..." dan nonaktifkan.
 * - Submit form #bulkPayForm.
 *
 * Ditugaskan ke window karena dipanggil dari atribut onclick inline pada
 * modal konfirmasi bayar massal (Vite memuat JS sebagai ES module, bukan global).
 */
window.submitBulkPayForm = function () {
    const checkedCheckboxes = document.querySelectorAll('.payroll-checkbox:checked');
    const bulkPayForm = document.getElementById('bulkPayForm');

    if (checkedCheckboxes.length === 0) {
        return; // Don't submit if nothing is selected
    }

    // Remove previous dynamic inputs
    const existingIds = bulkPayForm.querySelectorAll('input[name="ids[]"]');
    existingIds.forEach(input => input.remove());
    const existingDate = bulkPayForm.querySelector('input[name="payment_date"]');
    if (existingDate) existingDate.remove();

    // Add checked IDs to bulk pay form
    checkedCheckboxes.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = checkbox.value;
        bulkPayForm.appendChild(input);
    });

    // Add payment date (today)
    const dateInput = document.createElement('input');
    dateInput.type = 'hidden';
    dateInput.name = 'payment_date';
    dateInput.value = new Date().toISOString().split('T')[0];
    bulkPayForm.appendChild(dateInput);

    // Add loading state to bulk pay button
    const bulkPayBtn = document.getElementById('confirm-btn-bulkPayModal');
    if (bulkPayBtn) {
        bulkPayBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
        bulkPayBtn.disabled = true;
        bulkPayBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    // Submit form
    bulkPayForm.submit();
};

// ==========================================
// FORM SUBMIT HANDLERS
// ==========================================

/**
 * Menginisialisasi handler submit form modal Generate dan Edit.
 *
 * Semua memakai handleFormSubmit() (helper bersama) untuk status memuat
 * dengan label "Memproses..." dan pencegahan double submit; bila ditolak,
 * pengiriman dibatalkan.
 */
function initFormSubmitHandlers() {
    // Handle Generate Modal Submit
    const generateForm = document.querySelector('#generateModal form');
    if (generateForm) {
        generateForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn, undefined, 'Memproses...')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Handle Edit Modal Submits
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn, undefined, 'Memproses...')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// PANEL PENGELUARAN OPERASIONAL (Scroll List)
// ==========================================

/**
 * Menginisialisasi semua fungsionalitas halaman payroll saat DOM siap.
 *
 * Alur inisialisasi:
 * - Cache referensi elemen modal Generate (select bulan/tahun/minggu, hidden
 *   tanggal, loader, panel status, tombol generate).
 * - Bulan/tahun/minggu berubah → muat minggu + debounce pengecekan absensi.
 * - Nonaktifkan tombol generate di awal.
 * - Reset tampilan saat modal Generate ditutup (event modalClosed).
 * - Dropdown filter minggu (halaman index) + muat filter awal bila ada.
 * - Checkbox Pilih Semua & individu → perbarui status tombol aksi.
 * - Daftarkan handler submit form.
 */
document.addEventListener('DOMContentLoaded', function () {
    periodMonthSelect = document.getElementById('period_month');
    periodYearInput = document.getElementById('period_year');
    weekNumberSelect = document.getElementById('week_number');
    projectMultiWrapper = document.querySelector('#generateModal .searchable-multi-select-wrapper');
    periodStartDateInput = document.getElementById('period_start_date');
    periodEndDateInput = document.getElementById('period_end_date');
    checkingLoader = document.getElementById('checking-loader');
    allCompleteDiv = document.getElementById('all-complete');
    incompleteWarningDiv = document.getElementById('incomplete-warning');
    completeInfoDiv = document.getElementById('complete-info');
    alreadyGeneratedWarningDiv = document.getElementById('already-generated-warning');
    incompleteList = document.getElementById('incomplete-list');
    completeList = document.getElementById('complete-list');
    alreadyGeneratedList = document.getElementById('already-generated-list');
    noProjectWarningDiv = document.getElementById('no-project-warning');
    noProjectList = document.getElementById('no-project-list');
    generateSubmitBtn = document.querySelector('#generateModal button[type="submit"]');
    signatorySectionsContainer = document.getElementById('signatory-sections');
    additionalCostsSectionsContainer = document.getElementById('additional-costs-sections');

    // Catat pilihan penanda tangan ke map persisten (event delegation sekali,
    // bukan saat render ulang) agar pilihan tidak hilang saat blok dirender
    // ulang (user menambah/menghapus proyek lain).
    if (signatorySectionsContainer) {
        signatorySectionsContainer.addEventListener('click', function (e) {
            const option = e.target.closest('.searchable-option');
            if (!option) return;

            const wrapper = option.closest('.searchable-select-wrapper');
            const hidden = wrapper ? wrapper.querySelector('.searchable-select-hidden') : null;
            if (!hidden) return;

            const match = hidden.name.match(/\[([^\]]+)\]\[([^\]]+)\]$/);
            if (!match) return;

            const key = match[1] + '\u0000' + match[2];
            signatorySelections.set(key, {
                id: option.dataset.value,
                label: option.dataset.label || ''
            });
        });
    }

    // Load weeks when month or year changes in generate modal
    if (periodMonthSelect) {
        periodMonthSelect.addEventListener('change', function() {
            loadGenerateWeeks();
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 400);
        });
    }

    if (periodYearInput) {
        periodYearInput.addEventListener('input', function() {
            loadGenerateWeeks();
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 600);
        });
    }

    // Pilihan proyek berubah (multi-select) → debounce pengecekan absensi.
    // Memakai event delegation karena checkbox & tag dirender ulang oleh
    // komponen searchable-multi-select.
    if (projectMultiWrapper) {
        const scheduleCheck = function () {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 300);
        };

        // PENTING: komponen searchable-multi-select men-toggle checkbox secara
        // manual saat opsi diklik pada area teks (tanpa mengirim event 'change'
        // native). Karena itu section dinamis (penanda tangan & biaya lain-lain)
        // dirender ulang BAIK lewat event 'change' maupun 'click' pada opsi.
        // setTimeout(0) memastikan hidden input project_name[] sudah diperbarui
        // oleh handler komponen sebelum dibaca ulang.
        const refreshGenerateSections = function () {
            setTimeout(function () {
                scheduleCheck();
                renderSignatorySections();
                renderAdditionalCostSections();
            }, 0);
        };

        projectMultiWrapper.addEventListener('change', function (e) {
            if (e.target.matches('.searchable-multi-checkbox, .searchable-multi-select-all')) {
                refreshGenerateSections();
            }
        });

        projectMultiWrapper.addEventListener('click', function (e) {
            if (e.target.matches('.searchable-multi-tag-remove')) {
                refreshGenerateSections();
                return;
            }

            if (e.target.closest('.searchable-multi-option')) {
                refreshGenerateSections();
            }
        });
    }

    // Event delegation untuk tombol tambah/hapus baris biaya lain-lain.
    if (additionalCostsSectionsContainer) {
        // Live formatting jumlah biaya ke format ribuan Indonesia (mis. 10.000).
        additionalCostsSectionsContainer.addEventListener('input', function (e) {
            if (e.target.classList.contains('additional-cost-amount')) {
                window.formatCurrencyInput(e.target);
            }
        });

        additionalCostsSectionsContainer.addEventListener('click', function (e) {
            const addBtn = e.target.closest('.additional-cost-add-btn');
            if (addBtn) {
                addAdditionalCostRow(addBtn.closest('.additional-cost-block'));
                return;
            }

            const removeBtn = e.target.closest('.additional-cost-remove-btn');
            if (removeBtn) {
                const rows = removeBtn.closest('.additional-cost-rows');
                const row = removeBtn.closest('.additional-cost-row');
                const rowCount = rows.querySelectorAll('.additional-cost-row').length;

                if (rowCount > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                    });
                }
            }
        });
    }

    if (weekNumberSelect) {
        weekNumberSelect.addEventListener('change', function() {
            updatePeriodDateInputs();
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 300);
        });
    }

    // Initialize button state to disabled on page load
    if (generateSubmitBtn) {
        generateSubmitBtn.disabled = true;
        generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        generateSubmitBtn.classList.remove('hover:bg-success-hover');
    }

    // Render placeholder blok penanda tangan (belum ada proyek terpilih)
    renderSignatorySections();
    renderAdditionalCostSections();

    // Dropdown proyek bersama (filter index): searchable dengan pagination
    // 10 item per load dari Rekap Proyek.
    initAllProjectDropdowns();

    // Reset saat modal ditutup
    window.addEventListener('modalClosed', function(e) {
        if (e.detail === 'generateModal') {
            clearLoader();
            hideAllStatusPanels();
            periodMonthSelect.value = '';
            periodYearInput.value = String(config.currentYear || new Date().getFullYear());
            weekNumberSelect.innerHTML = '<option value="">Pilih bulan & tahun terlebih dahulu</option>';
            cachedWeeksData = [];
            periodStartDateInput.value = '';
            periodEndDateInput.value = '';

            // Reset pilihan proyek (multi-select) di modal generate
            resetProjectMultiSelect();

            // Bersihkan pilihan penanda tangan lalu render ulang blok (placeholder)
            signatorySelections.clear();
            renderSignatorySections();
            renderAdditionalCostSections();

            // Reset button state
            if (generateSubmitBtn) {
                generateSubmitBtn.disabled = true;
                generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                generateSubmitBtn.classList.remove('hover:bg-success-hover');
                generateSubmitBtn.title = '';
            }
        }
    });

    // Filter week dropdown
    filterMonthSelect = document.querySelector('select[name="month"]');
    filterYearInput = document.querySelector('select[name="year"]');
    filterWeekSelect = document.getElementById('filter_week_number');

    if (filterMonthSelect) {
        filterMonthSelect.addEventListener('change', loadFilterWeeks);
    }
    if (filterYearInput) {
        filterYearInput.addEventListener('change', loadFilterWeeks);
    }

    // Load filter weeks on page load if month/year are set
    if (config.filterMonth && config.filterYear) {
        loadFilterWeeks();
    }

    // Select All Checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateButtonStates();
            syncGroupSelectStates();
        });
    }

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]:not(:disabled)').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
            const checkedCheckboxes = document.querySelectorAll(
                'input[name="ids[]"]:not(:disabled):checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateButtonStates();
            syncGroupSelectStates();
        });
    });

    // Group Select All (per proyek + periode): centang semua payroll
    // (draft maupun paid) dalam satu grup.
    document.querySelectorAll('.group-select-all').forEach(groupCheck => {
        groupCheck.addEventListener('change', function() {
            const index = this.dataset.groupIndex;
            const checkboxes = document.querySelectorAll(
                `input[name="ids[]"][data-group-index="${index}"]:not(:disabled)`);
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });

            const selectAll = document.getElementById('selectAll');
            const allCheckboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
            const checkedAll = document.querySelectorAll('input[name="ids[]"]:not(:disabled):checked');
            if (selectAll) {
                selectAll.checked = allCheckboxes.length === checkedAll.length;
            }

            updateButtonStates();
        });
    });

    // Initialize button states on page load
    updateButtonStates();
    syncGroupSelectStates();

    // Form submit handlers
    initFormSubmitHandlers();
});
