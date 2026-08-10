/**
 * Searchable Multi-Select - Menginisialisasi dropdown multi-pilih yang dapat
 * dicari yang dibuat oleh komponen Blade <x-forms.searchable-multi-select>.
 *
 * Mendukung pemilihan banyak checkbox dengan tampilan tag dan input tersembunyi.
 * Penggunaan: panggil initSearchableMultiSelects() setelah DOM siap atau setelah modal terbuka.
 */
/**
 * Menginisialisasi seluruh komponen searchable multi-select dalam container
 * (atau seluruh dokumen bila container kosong).
 *
 * ALUR LENGKAP:
 * 1. Kumpulkan semua wrapper `.searchable-multi-select-wrapper` dari container.
 * 2. Untuk setiap wrapper yang belum diinisialisasi (dataset.multiSelectInitialized
 *    != 'true'), tandai sebagai sudah diinisialisasi.
 * 3. Ambil elemen penting wrapper: input pencarian, dropdown, opsi individual
 *    (.searchable-multi-options .searchable-multi-option), tombol select-all,
 *    container tag, container input tersembunyi, dan nama field dari
 *    dataset.name container hidden inputs.
 * 4. Bila input pencarian/dropdown tidak ada, lewati wrapper ini.
 * 5. State inti berupa Map `selectedValues` yang memetakan value -> label.
 *    Map dipakai agar: (a) penyimpanan unik (satu value satu entri),
 *    (b) urutan pemilihan terjaga, dan (c) akses nilai cepat.
 * 6. Registrasi event:
 *    - focus input  -> tampilkan dropdown.
 *    - input pencarian -> saring opsi individual berdasarkan dataset.search;
 *      tampilkan/sembunyikan pesan "no results"; lalu updateSelectAllState().
 *    - klik opsi individual -> toggle checkbox (kecuali klik tepat pada
 *      checkbox agar tidak double-toggle), lalu handleCheckboxChange().
 *    - change checkbox -> handleCheckboxChange().
 *    - change select-all -> iterasi opsi yang terlihat saja, set checkbox ke
 *      nilai target yang disimpan sebelum loop (karena handleCheckboxChange()
 *      memanggil updateSelectAllState() yang mengubah state select-all selama
 *      iterasi), lindungi dengan flag isSelectAllInProgress agar state select-all
 *      tidak dihitung ulang di tengah proses, lalu updateSelectAllState() di akhir.
 *    - klik di luar wrapper -> tutup dropdown.
 * 7. handleCheckboxChange(checkbox):
 *    - Ambil value & label dari opsi terdekat (fallback label = value).
 *    - Jika dicentang -> selectedValues.set(value, label);
 *      jika tidak -> selectedValues.delete(value).
 *    - Panggil renderTags(), renderHiddenInputs(), updateSelectAllState().
 * 8. renderTags():
 *    - Kosongkan container tag, lalu untuk tiap entri Map buat elemen <span>
 *      tag berisi label + tombol hapus (×) dengan data-value.
 *    - Tombol hapus pada tag: hapus value dari Map, uncheck checkbox terkait,
 *      lalu render ulang tag, hidden inputs, dan state select-all.
 * 9. renderHiddenInputs():
 *    - Kosongkan container, lalu untuk tiap value buat <input type="hidden"
 *      name="namaField[]" value="value"> agar terkirim saat submit form.
 * 10. updateSelectAllState():
 *    - Dilewati bila select-all tidak ada atau sedang proses select-all.
 *    - Hitung checkbox terlihat & jumlah yang dicentang; set checked bila semua
 *      tercentang, dan indeterminate bila sebagian tercentang.
 *
 * @param  {HTMLElement|Document}  [container]  Elemen pencarian; default document.
 * @returns {void}
 */
function initSearchableMultiSelects(container) {
    const wrappers = (container || document).querySelectorAll('.searchable-multi-select-wrapper');

    wrappers.forEach(function (wrapper) {
        if (wrapper.dataset.multiSelectInitialized === 'true') return;
        wrapper.dataset.multiSelectInitialized = 'true';

        const searchInput = wrapper.querySelector('.searchable-multi-select-input');
        const dropdown = wrapper.querySelector('.searchable-multi-dropdown');
        const options = wrapper.querySelectorAll('.searchable-multi-option');
        const individualOptions = wrapper.querySelectorAll('.searchable-multi-options .searchable-multi-option');
        const noResults = wrapper.querySelector('.searchable-multi-no-results');
        const selectAllCheckbox = wrapper.querySelector('.searchable-multi-select-all');
        const tagsContainer = wrapper.querySelector('.searchable-multi-tags');
        const hiddenInputsContainer = wrapper.querySelector('.searchable-multi-hidden-inputs');
        const name = wrapper.querySelector('.searchable-multi-hidden-inputs')?.dataset?.name || '';

        if (!searchInput || !dropdown) return;

        const selectedValues = new Map();
        var isSelectAllInProgress = false;

        /**
         * Tampilkan dropdown saat fokus
         */
        searchInput.addEventListener('focus', function () {
            dropdown.classList.remove('hidden');
        });

        /**
         * Cari / saring opsi (dengan debounce agar tidak memfilter ulang pada
         * setiap ketukan keyboard)
         */
        searchInput.addEventListener('input', window.debounce(function () {
            const searchTerm = this.value.toLowerCase();
            let hasResults = false;

            individualOptions.forEach(function (option) {
                const searchText = option.dataset.search || '';
                if (searchText.includes(searchTerm)) {
                    option.style.display = '';
                    hasResults = true;
                } else {
                    option.style.display = 'none';
                }
            });

            if (hasResults) {
                noResults.classList.add('hidden');
            } else {
                noResults.classList.remove('hidden');
            }

            updateSelectAllState();
        }, 200));

        /**
         * Menangani klik checkbox individual
         */
        individualOptions.forEach(function (option) {
            option.addEventListener('click', function (e) {
                if (e.target.classList.contains('searchable-multi-checkbox')) return;

                const checkbox = this.querySelector('.searchable-multi-checkbox');
                checkbox.checked = !checkbox.checked;
                handleCheckboxChange(checkbox);
            });

            const checkbox = option.querySelector('.searchable-multi-checkbox');
            if (checkbox) {
                checkbox.addEventListener('change', function () {
                    handleCheckboxChange(this);
                });
            }
        });

        /**
         * Menangani checkbox Select All.
         *
         * PENTING: Kita harus menyimpan state target (this.checked) sebelum perulangan,
         * karena handleCheckboxChange() memanggil updateSelectAllState() yang mengubah
         * selectAllCheckbox.checked berdasarkan state parsial selama iterasi.
         */
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                var shouldCheck = this.checked;
                isSelectAllInProgress = true;

                var isVisible = function (option) {
                    return option.style.display !== 'none';
                };

                individualOptions.forEach(function (option) {
                    if (isVisible(option)) {
                        var checkbox = option.querySelector('.searchable-multi-checkbox');
                        checkbox.checked = shouldCheck;
                        handleCheckboxChange(checkbox);
                    }
                });

                isSelectAllInProgress = false;
                updateSelectAllState();
            });
        }

        /**
         * Menangani perubahan checkbox - memperbarui nilai terpilih, tag, dan input tersembunyi
         *
         * @param {HTMLInputElement} checkbox
         */
        function handleCheckboxChange(checkbox) {
            const value = checkbox.value;
            const option = checkbox.closest('.searchable-multi-option');
            const label = option?.dataset.label || value;

            if (checkbox.checked) {
                selectedValues.set(value, label);
            } else {
                selectedValues.delete(value);
            }

            renderTags();
            renderHiddenInputs();
            updateSelectAllState();
        }

        /**
         * Render item terpilih sebagai tag di bawah input
         */
        function renderTags() {
            tagsContainer.innerHTML = '';

            selectedValues.forEach(function (label, value) {
                const tag = document.createElement('span');
                tag.className = 'inline-flex items-center gap-1 px-2 py-1 bg-primary-light text-primary text-xs font-medium rounded-full';
                tag.innerHTML = label +
                    ' <button type="button" class="searchable-multi-tag-remove hover:text-error" data-value="' + value + '">&times;</button>';
                tagsContainer.appendChild(tag);
            });

            tagsContainer.querySelectorAll('.searchable-multi-tag-remove').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const removeValue = this.dataset.value;
                    selectedValues.delete(removeValue);

                    const checkbox = wrapper.querySelector('.searchable-multi-checkbox[value="' + removeValue + '"]');
                    if (checkbox) checkbox.checked = false;

                    renderTags();
                    renderHiddenInputs();
                    updateSelectAllState();
                });
            });
        }

        /**
         * Render input tersembunyi untuk pengiriman form
         */
        function renderHiddenInputs() {
            const fieldName = hiddenInputsContainer.dataset.name || name;
            hiddenInputsContainer.innerHTML = '';

            selectedValues.forEach(function (label, value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = fieldName + '[]';
                input.value = value;
                hiddenInputsContainer.appendChild(input);
            });
        }

        /**
         * Perbarui state checkbox Select All berdasarkan checkbox yang terlihat dan dicentang.
         * Dilewati selama iterasi Select All untuk mencegah kedipan (flickering).
         */
        function updateSelectAllState() {
            if (!selectAllCheckbox || isSelectAllInProgress) return;

            const visibleCheckboxes = [];
            individualOptions.forEach(function (option) {
                if (option.style.display !== 'none') {
                    const cb = option.querySelector('.searchable-multi-checkbox');
                    if (cb) visibleCheckboxes.push(cb);
                }
            });

            const checkedCount = visibleCheckboxes.filter(function (cb) {
                return cb.checked;
            }).length;

            selectAllCheckbox.checked = visibleCheckboxes.length > 0 && checkedCount === visibleCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < visibleCheckboxes.length;
        }

        /**
         * Tutup dropdown saat mengklik di luar
         */
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
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
