/**
 * Dropdown Proyek Bersama — searchable + pagination (10 item per load).
 *
 * Dipakai pada halaman Karyawan (modal tambah/edit) dan halaman Payroll
 * (filter & modal generate) serta filter Data Karyawan. Data proyek diambil
 * dari Rekap Proyek via AJAX (route employee.projects-dropdown) dengan
 * pencarian (debounce 300ms) dan infinite scroll 10 item per batch.
 *
 * Konfigurasi dibaca dari data attributes pada root .project-dropdown:
 * - data-route       : URL endpoint AJAX (wajib)
 * - data-placeholder : teks label saat belum ada pilihan
 *                      (default: "-- Pilih Proyek --")
 * - data-all-option  : jika diisi, baris "pilih semua / kosongkan" ditampilkan
 *                      di bagian atas daftar (mis. "Semua Proyek")
 * - data-auto-submit : "1" → submit form terdekat setelah memilih
 *
 * Saat pilihan berubah (pilih proyek / pilih semua), event 'change' di-dispatch
 * pada hidden input agar listener eksternal (mis. pengecekan absensi payroll)
 * tetap berjalan. Untuk mereset pilihan secara programatik, gunakan
 * resetProjectDropdown(root) yang juga mengembalikan label ke placeholder.
 *
 * Struktur markup yang diharapkan (lihat komponen
 * components/filters/project-filter.blade.php dan modal karyawan):
 *   <div class="project-dropdown" data-route="..." ...>
 *       <input type="hidden" name="project_name" class="project-dropdown-hidden">
 *       <button type="button" class="project-dropdown-toggle">...</button>
 *       <div class="project-dropdown-menu hidden">...</div>
 *   </div>
 */

/**
 * Inisialisasi satu dropdown proyek.
 *
 * @param {HTMLElement} dropdownRoot  Root elemen .project-dropdown
 * @returns {void}
 */
export function initProjectDropdown(dropdownRoot) {
    const hiddenInput = dropdownRoot.querySelector('.project-dropdown-hidden');
    const toggleBtn = dropdownRoot.querySelector('.project-dropdown-toggle');
    const label = dropdownRoot.querySelector('.project-dropdown-label');
    const menu = dropdownRoot.querySelector('.project-dropdown-menu');
    const searchInput = dropdownRoot.querySelector('.project-dropdown-search');
    const list = dropdownRoot.querySelector('.project-dropdown-list');
    const clearBtn = dropdownRoot.querySelector('.project-dropdown-clear');

    if (!hiddenInput || !toggleBtn || !menu || !list) return;

    const route = dropdownRoot.dataset.route;
    const placeholder = dropdownRoot.dataset.placeholder || '-- Pilih Proyek --';
    const allOption = dropdownRoot.dataset.allOption || '';
    const autoSubmit = dropdownRoot.dataset.autoSubmit === '1';

    /** @type {{ page: number, limit: number, loading: boolean, hasMore: boolean, search: string }} */
    const state = {
        page: 1,
        limit: 10,
        loading: false,
        hasMore: true,
        search: '',
    };

    /**
     * Menyubmit form terdekat (untuk mode filter).
     *
     * @returns {void}
     */
    function submitForm() {
        const form = dropdownRoot.closest('form');
        if (form && typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        }
    }

    /**
     * Mengisi pilihan proyek lalu menutup menu.
     *
     * @param {string} value  Nilai proyek (nama proyek)
     * @returns {void}
     */
    function selectProject(value) {
        hiddenInput.value = value;
        label.textContent = value || placeholder;
        menu.classList.add('hidden');
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        if (autoSubmit) {
            submitForm();
        }
    }

    /**
     * Merender baris "pilih semua / kosongkan filter" di bagian atas daftar.
     *
     * @returns {void}
     */
    function renderAllOption() {
        if (!allOption) return;

        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'w-full text-left px-3 py-2 hover:bg-surface-secondary text-sm text-text-primary font-medium border-b border-border-light';
        row.textContent = allOption;

        row.addEventListener('click', function () {
            selectProject('');
        });

        list.appendChild(row);
    }

    /**
     * Mengambil data proyek dari server untuk dropdown.
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
                renderAllOption();
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
                        selectProject(project.project_name);
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
                renderAllOption();
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
        const debouncedSearch = window.debounce(function () {
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
            selectProject('');
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

/**
 * Inisialisasi semua dropdown proyek di dalam root.
 *
 * @param {Document|HTMLElement} [root]  Elemen yang dicari (default: document)
 * @returns {void}
 */
export function initAllProjectDropdowns(root = document) {
    root.querySelectorAll('.project-dropdown').forEach(initProjectDropdown);
}

/**
 * Mereset pilihan dropdown proyek secara programatik (kosongkan value,
 * kembalikan label ke placeholder) dan men-dispatch event 'change'.
 *
 * Dipakai untuk reset form/modal (mis. saat modal Generate payroll ditutup).
 *
 * @param {HTMLElement} dropdownRoot  Root elemen .project-dropdown
 * @returns {void}
 */
export function resetProjectDropdown(dropdownRoot) {
    if (!dropdownRoot) return;

    const hiddenInput = dropdownRoot.querySelector('.project-dropdown-hidden');
    const label = dropdownRoot.querySelector('.project-dropdown-label');
    if (!hiddenInput) return;

    const placeholder = dropdownRoot.dataset.placeholder || '-- Pilih Proyek --';
    hiddenInput.value = '';
    if (label) {
        label.textContent = placeholder;
    }
    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
}
