/**
 * Searchable Multi-Select - Menginisialisasi dropdown multi-pilih yang dapat
 * dicari yang dibuat oleh komponen Blade <x-forms.searchable-multi-select>.
 *
 * Mendukung pemilihan banyak checkbox dengan tampilan tag dan input tersembunyi.
 * Penggunaan: panggil initSearchableMultiSelects() setelah DOM siap atau setelah modal terbuka.
 */
/**
 * State per wrapper multi-select.
 *
 * Disimpan di WeakMap agar:
 * - Listener diikat CUKUP SEKALI (event delegation pada wrapper) dan tidak
 *   menumpuk ketika wrapper di-reset via pola `delete dataset.multiSelectInitialized`
 *   lalu init ulang (dipakai loadEligibleEmployees / resetProjectMultiSelect).
 * - Opsi yang dirender ulang via AJAX (innerHTML pada .searchable-multi-options)
 *   tetap berfungsi tanpa perlu rebind, karena delegasi event menyatu pada wrapper.
 *
 * Alur inisialisasi:
 * 1. Jika wrapper sudah bertanda initialized (dataset.multiSelectInitialized) →
 *    tidak melakukan apa-apa (mis. dipanggil ulang oleh openModal).
 * 2. Jika state lama ada di WeakMap (pola reset: flag dihapus lalu init ulang) →
 *    cukup kosongkan pilihan (reset) tanpa mengikat listener baru.
 * 3. Jika benar-benar baru → buat state, ikat listener sekali, terapkan preselection.
 */
const multiSelectStates = new WeakMap();

/**
 * Menginisialisasi seluruh komponen searchable multi-select dalam container
 * (atau seluruh dokumen bila container kosong).
 *
 * @param  {HTMLElement|Document}  [container]  Elemen pencarian; default document.
 * @returns {void}
 */
function initSearchableMultiSelects(container) {
    // Container boleh berupa elemen wrapper itu sendiri (dipanggil langsung
    // setelah opsi dirender ulang via AJAX). Dalam hal itu proses wrapper
    // tersebut; bila tidak, ambil semua wrapper di dalam container/dokumen.
    let wrappers;
    if (container && typeof container.querySelectorAll !== 'function') {
        wrappers = [];
    } else if (container && container.classList && container.classList.contains('searchable-multi-select-wrapper')) {
        wrappers = [container];
    } else {
        wrappers = (container || document).querySelectorAll('.searchable-multi-select-wrapper');
    }

    wrappers.forEach(function (wrapper) {
        if (wrapper.dataset.multiSelectInitialized === 'true') return;
        wrapper.dataset.multiSelectInitialized = 'true';

        const state = multiSelectStates.get(wrapper);

        if (state) {
            // Re-init via pola reset: kosongkan pilihan, JANGAN ikat ulang listener.
            state.reset();
            return;
        }

        const newState = createMultiSelectState(wrapper);
        if (!newState) return;

        multiSelectStates.set(wrapper, newState);
        bindMultiSelectListeners(wrapper, newState);
        newState.initPreselection();
    });
}

/**
 * Membuat state komponen untuk satu wrapper.
 *
 * Semua akses ke opsi dilakukan via query DOM saat dibutuhkan (bukan
 * menyimpan NodeList statis) sehingga perubahan opsi via AJAX selalu terlihat.
 *
 * @param  {HTMLElement}  wrapper  Elemen .searchable-multi-select-wrapper.
 * @returns {object|null}  State komponen, atau null bila struktur tidak valid.
 */
function createMultiSelectState(wrapper) {
    const searchInput = wrapper.querySelector('.searchable-multi-select-input');
    const dropdown = wrapper.querySelector('.searchable-multi-dropdown');

    if (!searchInput || !dropdown) return null;

    const noResults = wrapper.querySelector('.searchable-multi-no-results');
    const selectAllCheckbox = wrapper.querySelector('.searchable-multi-select-all');
    const tagsContainer = wrapper.querySelector('.searchable-multi-tags');
    const hiddenInputsContainer = wrapper.querySelector('.searchable-multi-hidden-inputs');
    const fieldName = (hiddenInputsContainer && hiddenInputsContainer.dataset.name) || '';

    const selectedValues = new Map();

    const state = {
        wrapper,
        searchInput,
        dropdown,
        noResults,
        selectAllCheckbox,
        tagsContainer,
        hiddenInputsContainer,
        fieldName,
        selectedValues,
        isSelectAllInProgress: false,
        PAGE_SIZE: 10,
        visibleLimit: 10,
        searchTerm: '',
    };

    /**
     * Opsi individual (di dalam .searchable-multi-options); tanpa opsi
     * "Pilih Semua" yang berada di luar container tersebut.
     */
    state.getIndividualOptions = function () {
        return wrapper.querySelectorAll('.searchable-multi-options .searchable-multi-option');
    };

    /**
     * Checkbox opsi yang sedang terlihat (tidak disembunyikan filter pencarian).
     */
    state.getVisibleCheckboxes = function () {
        const checkboxes = [];

        state.getIndividualOptions().forEach(function (option) {
            if (option.style.display !== 'none') {
                const checkbox = option.querySelector('.searchable-multi-checkbox');
                if (checkbox) checkboxes.push(checkbox);
            }
        });

        return checkboxes;
    };

    /**
     * Terapkan filter pencarian + batas tampilan (10 opsi awal).
     * Opsi di luar `visibleLimit` disembunyikan kecuali saat scroll diperluas.
     */
    state.applyFilter = function () {
        let matchIndex = 0;
        let hasResults = false;

        state.getIndividualOptions().forEach(function (option) {
            const matches = (option.dataset.search || '').includes(state.searchTerm);
            if (matches) {
                hasResults = true;
                option.style.display = (matchIndex < state.visibleLimit) ? '' : 'none';
                matchIndex++;
            } else {
                option.style.display = 'none';
            }
        });

        const optionsDiv = state.wrapper.querySelector('.searchable-multi-options');
        if (optionsDiv) optionsDiv.classList.toggle('hidden', !hasResults);
        if (state.noResults) state.noResults.classList.toggle('hidden', hasResults);

        state.updateSelectAllState();
    };

    /**
     * Render item terpilih sebagai tag di bawah input.
     */
    state.renderTags = function () {
        if (!tagsContainer) return;

        tagsContainer.innerHTML = '';

        selectedValues.forEach(function (label, value) {
            const tag = document.createElement('span');
            tag.className = 'inline-flex items-center gap-1 px-2 py-1 bg-primary-light text-primary text-xs font-medium rounded-full';
            tag.innerHTML = label +
                ' <button type="button" class="searchable-multi-tag-remove hover:text-error" data-value="' + value + '">&times;</button>';
            tagsContainer.appendChild(tag);
        });
    };

    /**
     * Render input tersembunyi untuk pengiriman form.
     */
    state.renderHiddenInputs = function () {
        if (!hiddenInputsContainer) return;

        hiddenInputsContainer.innerHTML = '';

        selectedValues.forEach(function (label, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = fieldName + '[]';
            input.value = value;
            hiddenInputsContainer.appendChild(input);
        });
    };

    /**
     * Perbarui state checkbox Select All berdasarkan checkbox yang terlihat.
     * Dilewati selama iterasi Select All untuk mencegah kedipan (flickering).
     */
    state.updateSelectAllState = function () {
        if (!selectAllCheckbox || state.isSelectAllInProgress) return;

        const visible = state.getVisibleCheckboxes();
        const checkedCount = visible.filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        selectAllCheckbox.checked = visible.length > 0 && checkedCount === visible.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < visible.length;
    };

    /**
     * Menangani perubahan checkbox - memperbarui nilai terpilih, tag, dan input tersembunyi.
     *
     * @param {HTMLInputElement} checkbox
     */
    state.handleCheckboxChange = function (checkbox) {
        const value = checkbox.value;
        const option = checkbox.closest('.searchable-multi-option');
        const label = (option && option.dataset.label) || value;

        if (checkbox.checked) {
            selectedValues.set(value, label);
        } else {
            selectedValues.delete(value);
        }

        state.renderTags();
        state.renderHiddenInputs();
        state.updateSelectAllState();
    };

    /**
     * Preseleksi: nilai awal dari atribut data-selected (mis. saat edit).
     * Nilai yang tidak ada pada daftar opsi tetap dirender sebagai tag.
     */
    state.initPreselection = function () {
        try {
            const preselected = JSON.parse(wrapper.dataset.selected || '[]');

            if (Array.isArray(preselected)) {
                preselected.forEach(function (value) {
                    if (value === '' || value === null || value === undefined) return;

                    let matched = null;
                    state.getIndividualOptions().forEach(function (option) {
                        const checkbox = option.querySelector('.searchable-multi-checkbox');
                        if (checkbox && checkbox.value === value && !matched) {
                            checkbox.checked = true;
                            matched = option;
                        }
                    });

                    const label = (matched && matched.dataset.label) || value;
                    selectedValues.set(value, label);
                });
            }
        } catch (e) {
            // abaikan data-selected yang tidak valid
        }

        if (selectedValues.size > 0) {
            state.renderTags();
            state.renderHiddenInputs();
            state.updateSelectAllState();
        }
    };

    /**
     * Mereset pilihan tanpa melepas listener (dipakai pola re-init/reset).
     */
    state.reset = function () {
        selectedValues.clear();

        wrapper.querySelectorAll('.searchable-multi-checkbox').forEach(function (checkbox) {
            checkbox.checked = false;
        });

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }

        state.renderTags();
        state.renderHiddenInputs();
        state.updateSelectAllState();
    };

    return state;
}

/**
 * Mengikat seluruh listener komponen ke wrapper (event delegation).
 *
 * Listener hanya diikat SEKALI per wrapper pada init pertama. Opsi yang
 * dirender ulang (innerHTML) tetap tertangkap karena event berjalan melalui
 * wrapper. Karena itu pola reset (hapus flag lalu init ulang) TIDAK menumpuk
 * listener ganda pada input pencarian / checkbox Select All / document.
 *
 * @param  {HTMLElement}  wrapper  Elemen .searchable-multi-select-wrapper.
 * @param  {object}       state    State komponen dari createMultiSelectState.
 * @returns {void}
 */
function bindMultiSelectListeners(wrapper, state) {
    // Tampilkan dropdown saat fokus
    state.searchInput.addEventListener('focus', function () {
        state.dropdown.classList.remove('hidden');
        state.applyFilter();
    });

    // Cari / saring opsi (dengan debounce agar tidak memfilter ulang pada
    // setiap ketukan keyboard)
    state.searchInput.addEventListener('input', window.debounce(function () {
        state.searchTerm = this.value.toLowerCase();
        state.visibleLimit = state.PAGE_SIZE; // reset ke 10 awal saat cari baru
        state.applyFilter();
    }, 200));

    // Infinite scroll: muat 10 opsi berikutnya saat mendekati dasar dropdown
    state.dropdown.addEventListener('scroll', function () {
        if (this.scrollTop + this.clientHeight >= this.scrollHeight - 5) {
            state.visibleLimit += state.PAGE_SIZE;
            state.applyFilter();
        }
    });

    // Delegasi klik pada wrapper: tombol hapus tag + area opsi.
    //
    // PENTING (perbaikan bug): tanpa preventDefault, klik pada teks opsi yang
    // dibungkus <label> akan men-toggle checkbox DUA KALI — sekali oleh kode di
    // bawah ini dan sekali lagi oleh perilaku default label yang meneruskan klik
    // ke checkbox. Akibatnya pemilihan tidak pernah berubah (seolah "tidak bisa
    // diklik"). preventDefault pada klik area teks mencegah penerusan tersebut.
    wrapper.addEventListener('click', function (e) {
        const tagRemove = e.target.closest('.searchable-multi-tag-remove');
        if (tagRemove) {
            e.preventDefault();
            const removeValue = tagRemove.dataset.value;

            state.selectedValues.delete(removeValue);

            const checkbox = wrapper.querySelector('.searchable-multi-checkbox[value="' + removeValue + '"]');
            if (checkbox) checkbox.checked = false;

            state.renderTags();
            state.renderHiddenInputs();
            state.updateSelectAllState();
            return;
        }

        const option = e.target.closest('.searchable-multi-option');
        if (!option) return;

        // "Pilih Semua" ditangani lewat event change checkbox-nya (label default
        // cukup men-toggle sekali tanpa kode tambahan di sini).
        if (option.dataset.value === '__select_all__') return;

        // Klik tepat pada checkbox: biarkan perilaku default (change event
        // menanganinya) agar tidak double-toggle.
        if (e.target.classList.contains('searchable-multi-checkbox')) return;

        // Klik pada area teks opsi: cegah penerusan klik oleh label, lalu
        // toggle checkbox secara manual.
        e.preventDefault();
        const checkbox = option.querySelector('.searchable-multi-checkbox');
        checkbox.checked = !checkbox.checked;
        state.handleCheckboxChange(checkbox);
    });

    // Delegasi change: checkbox opsi & checkbox "Pilih Semua".
    wrapper.addEventListener('change', function (e) {
        if (e.target.classList.contains('searchable-multi-checkbox')) {
            state.handleCheckboxChange(e.target);
            return;
        }

        if (e.target.classList.contains('searchable-multi-select-all')) {
            // Simpan state target sebelum perulangan karena handleCheckboxChange
            // memanggil updateSelectAllState() yang mengubah checkbox ini.
            const shouldCheck = e.target.checked;
            state.isSelectAllInProgress = true;

            state.getVisibleCheckboxes().forEach(function (checkbox) {
                checkbox.checked = shouldCheck;
                state.handleCheckboxChange(checkbox);
            });

            state.isSelectAllInProgress = false;
            state.updateSelectAllState();
        }
    });

    // Tutup dropdown saat mengklik di luar
    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            state.dropdown.classList.add('hidden');
        }
    });
}

/**
 * Ekspos initSearchableMultiSelects ke global window agar bisa dipanggil
 * manual setelah modal terbuka / konten di-render dinamis.
 *
 * @returns {void}
 */
window.initSearchableMultiSelects = initSearchableMultiSelects;

/**
 * Inisialisasi otomatis saat DOM siap.
 *
 * Jika dokumen masih loading, tunggu event DOMContentLoaded; jika sudah siap,
 * langsung jalankan agar komponen yang sudah dirender langsung berfungsi.
 *
 * @returns {void}
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        initSearchableMultiSelects();
    });
} else {
    initSearchableMultiSelects();
}
