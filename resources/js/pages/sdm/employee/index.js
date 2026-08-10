/**
 * Logika halaman Karyawan (Data Karyawan).
 *
 * Menangani:
 * - Checkbox Pilih Semua / Batalkan Pilih Semua
 * - Manajemen status checkbox individu dan tombol hapus
 * - Pengiriman formulir hapus massal dengan status memuat
 * - Format mata uang upah harian pada input
 * - Penanganan pengiriman formulir Tambah/Edit dengan pencegahan pengiriman ganda
 */

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
// Project Dropdown (Searchable + Pagination)
// ==========================================

/**
 * Menginisialisasi satu dropdown proyek (searchable + infinite scroll).
 *
 * Mengikuti pola dropdown infinite scroll pada modul Laporan Stok:
 * - Data proyek diambil dari Rekap Proyek via AJAX (route employee.projects-dropdown).
 * - Mendukung pencarian (debounce 300ms) dan pagination 10 item per load.
 *
 * Alur:
 * 1. Baca konfigurasi route dari dataset.route pada root .project-dropdown.
 * 2. Toggle tombol membuka/menutup menu; saat dibuka selalu reset & muat ulang.
 * 3. Input pencarian memfilter via server (debounce).
 * 4. Scroll mendekati dasar memuat batch berikutnya (infinite scroll).
 * 5. Klik opsi mengisi hidden input (project_name) dan label.
 * 6. Tombol reset mengosongkan pilihan.
 *
 * @param {HTMLElement} dropdownRoot  Root elemen .project-dropdown
 * @returns {void}
 */
function initProjectDropdown(dropdownRoot) {
    const hiddenInput = dropdownRoot.querySelector('.project-dropdown-hidden');
    const toggleBtn = dropdownRoot.querySelector('.project-dropdown-toggle');
    const label = dropdownRoot.querySelector('.project-dropdown-label');
    const menu = dropdownRoot.querySelector('.project-dropdown-menu');
    const searchInput = dropdownRoot.querySelector('.project-dropdown-search');
    const list = dropdownRoot.querySelector('.project-dropdown-list');
    const clearBtn = dropdownRoot.querySelector('.project-dropdown-clear');

    if (!hiddenInput || !toggleBtn || !menu || !list) return;

    const route = dropdownRoot.dataset.route;

    /** @type {{ page: number, limit: number, loading: boolean, hasMore: boolean, search: string }} */
    const state = {
        page: 1,
        limit: 10,
        loading: false,
        hasMore: true,
        search: '',
    };

    /**
     * Mengambil data proyek dari server untuk dropdown.
     * Mendukung pencarian dan pagination (infinite scroll, 10 item per load).
     *
     * @param {boolean} append true = tambahkan data, false = ganti data
     * @returns {void}
     */
    function fetchProjects(append) {
        if (state.loading) return;
        state.loading = true;

        if (!append) {
            list.innerHTML = '<div class="p-2 flex items-center gap-2 text-sm text-text-secondary">' +
                '<span class="inline-block w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>' +
                '<span>Loading...</span></div>';
        }

        const params = new URLSearchParams({
            search: state.search,
            page: String(state.page),
            limit: String(state.limit),
        });

        fetch(`${route}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function (res) {
            const data = res.data || [];

            if (!append) {
                list.innerHTML = '';
            }

            if (data.length === 0 && !append) {
                const el = document.createElement('div');
                el.className = 'p-2 text-sm text-text-secondary';
                el.textContent = state.search ? 'Tidak ada proyek yang cocok' : 'Tidak ada data proyek';
                list.appendChild(el);
            } else {
                data.forEach(function (project) {
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'w-full text-left px-3 py-2 hover:bg-surface-secondary text-sm text-text-primary border-b border-border-light';
                    row.textContent = project.project_name;

                    row.addEventListener('click', function () {
                        hiddenInput.value = project.project_name;
                        label.textContent = project.project_name;
                        menu.classList.add('hidden');
                    });

                    list.appendChild(row);
                });
            }

            state.hasMore = !!res.hasMore;
            if (state.hasMore) {
                state.page += 1;
            }
        })
        .catch(function () {
            if (!append) {
                list.innerHTML = '';
            }
            const el = document.createElement('div');
            el.className = 'p-2 text-sm text-error';
            el.textContent = 'Gagal memuat data proyek. Silakan coba lagi.';
            list.appendChild(el);
        })
        .finally(function () {
            state.loading = false;
        });
    }

    /**
     * Mereset pagination dan memuat ulang data dari server.
     * Digunakan saat search query berubah atau dropdown dibuka.
     *
     * @returns {void}
     */
    function resetAndFetch() {
        state.page = 1;
        state.hasMore = true;
        state.loading = false;
        fetchProjects(false);
    }

    // Toggle menu dropdown
    toggleBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('hidden');

        if (!menu.classList.contains('hidden')) {
            if (searchInput) {
                setTimeout(function () { searchInput.focus(); }, 50);
            }
            resetAndFetch();
        }
    });

    // Search input dengan debounce
    if (searchInput) {
        const debouncedSearch = debounce(function () {
            const query = searchInput.value.trim();
            if (query !== state.search) {
                state.search = query;
                resetAndFetch();
            }
        }, 300);

        searchInput.addEventListener('input', debouncedSearch);
        searchInput.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // Clear selection (reset ke tidak ada proyek)
    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (searchInput) {
                searchInput.value = '';
            }
            state.search = '';
            hiddenInput.value = '';
            label.textContent = '-- Pilih Proyek --';
            menu.classList.add('hidden');
        });
    }

    // Infinite scroll: load more saat scroll mendekati bawah
    list.addEventListener('scroll', function () {
        const nearBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 20;
        if (nearBottom && !state.loading && state.hasMore) {
            fetchProjects(true);
        }
    });

    // Tutup dropdown saat klik di luar
    document.addEventListener('click', function () {
        menu.classList.add('hidden');
    });

    // Cegah dropdown tertutup saat klik di dalam menu
    menu.addEventListener('click', function (e) {
        e.stopPropagation();
    });
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

    // Inisialisasi dropdown proyek (searchable + pagination) di semua modal
    document.querySelectorAll('.project-dropdown').forEach(function (dropdown) {
        initProjectDropdown(dropdown);
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
