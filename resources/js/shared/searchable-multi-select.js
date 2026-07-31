/**
 * Searchable Multi-Select - Menginisialisasi dropdown multi-pilih yang dapat
 * dicari yang dibuat oleh komponen Blade <x-forms.searchable-multi-select>.
 *
 * Mendukung pemilihan banyak checkbox dengan tampilan tag dan input tersembunyi.
 * Penggunaan: panggil initSearchableMultiSelects() setelah DOM siap atau setelah modal terbuka.
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
         * Cari / saring opsi
         */
        searchInput.addEventListener('input', function () {
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
        });

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

window.initSearchableMultiSelects = initSearchableMultiSelects;

// Inisialisasi otomatis saat DOM siap
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        initSearchableMultiSelects();
    });
} else {
    initSearchableMultiSelects();
}
