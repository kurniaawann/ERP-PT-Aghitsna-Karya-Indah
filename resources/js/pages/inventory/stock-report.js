/**
 * Laporan Stok Barang - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Dropdown pemilihan barang dengan search dan infinite scroll
 * - Auto-filter saat tanggal atau barang berubah
 * - AJAX fetch data barang untuk dropdown (10 item per load)
 * - Reset pilihan barang
 * - Penanganan error AJAX dengan toast notification
 */

/**
 * Mengambil konfigurasi route dari window.stockReportConfig.
 * Route URLs di-inject dari Blade template.
 *
 * @returns {{ indexRoute: string, itemsDropdownRoute: string }}
 */
function getConfig() {
    return window.stockReportConfig || { 
        indexRoute: '/stock-report',
        itemsDropdownRoute: '/stock-report/items-dropdown',
    };
}

/**
 * Mendapatkan nilai tanggal mulai dan akhir dari form.
 *
 * @returns {{ start: string, end: string }}
 */
function getCurrentDates() {
    const start = document.getElementById('start_date')?.value;
    const end = document.getElementById('end_date')?.value;
    return { start, end };
}

/**
 * Melakukan auto-filter (redirect) berdasarkan nilai form saat ini.
 * Dipanggil saat user memilih atau mereset barang.
 *
 * @param {HTMLInputElement} hiddenInput  Hidden input untuk item_id
 */
function autoFilterNow(hiddenInput) {
    const config = getConfig();
    const { start, end } = getCurrentDates();

    const url = new URL(config.indexRoute, window.location.origin);

    if (start) url.searchParams.set('start_date', start);
    if (end) url.searchParams.set('end_date', end);

    // item_id boleh kosong untuk "semua barang"
    url.searchParams.set('item_id', hiddenInput.value || '');

    // reset pagination ke page 1
    url.searchParams.delete('page');

    window.location.href = url.toString();
}

/**
 * Mengatur pilihan barang pada dropdown dan memicu auto-filter.
 *
 * @param {HTMLInputElement} hiddenInput  Hidden input untuk item_id
 * @param {HTMLElement}      label        Label dropdown untuk menampilkan teks terpilih
 * @param {HTMLElement}      menu         Container dropdown menu
 * @param {string}           id           ID barang yang dipilih (kosong untuk reset)
 * @param {string}           text         Teks yang ditampilkan pada label
 */
function setSelected(hiddenInput, label, menu, id, text) {
    hiddenInput.value = id || '';
    label.textContent = text || '- Semua Barang -';
    menu.classList.add('hidden');

    // auto submit/filter saat user memilih barang
    autoFilterNow(hiddenInput);
}

/**
 * Mengambil data barang dari server untuk dropdown.
 * Mendukung pencarian dan pagination (infinite scroll, 10 item per load).
 *
 * @param {Object}     state              State dropdown
 * @param {number}     state.page         Halaman saat ini
 * @param {number}     state.limit        Jumlah item per halaman (default: 10)
 * @param {boolean}    state.loading      Status loading
 * @param {boolean}    state.hasMore      Masih ada data berikutnya
 * @param {string}     state.search       Kata kunci pencarian
 * @param {HTMLElement} list               Container list dropdown
 * @param {boolean}    append             true = tambahkan data, false = ganti data
 * @returns {Promise<void>}
 */
async function fetchItems(state, list, append = false) {
    if (state.loading || (!append && !state.hasMore && state.page > 1)) return;
    if (state.loading) return;
    state.loading = true;

    const config = getConfig();
    const params = new URLSearchParams({
        search: state.search,
        page: String(state.page),
        limit: String(state.limit),
    });

    // Tampilkan loading spinner jika bukan append
    if (!append) {
        list.innerHTML = '';
        const loadingEl = document.createElement('div');
        loadingEl.className = 'p-2 flex items-center gap-2 text-sm text-text-secondary';
        loadingEl.innerHTML = `
            <span class="inline-block w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
            <span>Loading...</span>
        `;
        list.appendChild(loadingEl);
    }

    try {
        const response = await fetch(`${config.itemsDropdownRoute}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const res = await response.json();
        const data = res.data || [];

        // Hapus loading spinner saat bukan append
        if (!append) {
            list.innerHTML = '';
        }

        if (data.length === 0 && !append) {
            const el = document.createElement('div');
            el.className = 'p-2 text-sm text-text-secondary';
            el.textContent = state.search ? 'Tidak ada barang yang cocok' : 'Tidak ada data barang';
            list.appendChild(el);
        } else {
            data.forEach(function (item) {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'w-full text-left px-3 py-2 hover:bg-surface-secondary text-sm text-text-primary';
                row.textContent = `${item.id_item} - ${item.name_item}`;
                row.dataset.id = item.id_item;

                row.addEventListener('click', function () {
                    setSelected(
                        document.getElementById('item_id'),
                        document.getElementById('itemDropdownLabel'),
                        document.getElementById('itemDropdownMenu'),
                        item.id_item,
                        row.textContent
                    );
                });

                list.appendChild(row); //tempel ke dropdown
            });
        }

        state.hasMore = !!res.hasMore;
        if (state.hasMore) {
            state.page += 1;
        }
    } catch (error) {
        // Hapus loading spinner saat error
        if (!append) {
            list.innerHTML = '';
        }

        const el = document.createElement('div');
        el.className = 'p-2 text-sm text-error';
        el.textContent = 'Gagal memuat data barang. Silakan coba lagi.';
        list.appendChild(el);

        // Tampilkan toast error jika tersedia
        if (typeof window.showToast === 'function') {
            window.showToast('Gagal memuat data barang. Silakan coba lagi.', 'error');
        }
    } finally {
        state.loading = false;
    }
}

/**
 * Menginisialisasi dropdown barang dengan search dan infinite scroll.
 * Mengikat event listener untuk toggle menu, search input,
 * clear selection, infinite scroll, dan outside click.
 */
function initItemDropdown() {
    const btn = document.getElementById('itemDropdownBtn');
    const menu = document.getElementById('itemDropdownMenu');
    const list = document.getElementById('itemDropdownList');
    const label = document.getElementById('itemDropdownLabel');
    const hiddenInput = document.getElementById('item_id');
    const clearBtn = document.getElementById('clearItemBtn');
    const searchInput = document.getElementById('itemSearchInput');

    if (!btn || !menu || !list) return;

    /** @type {{ page: number, limit: number, loading: boolean, hasMore: boolean, search: string }} */
    const state = {
        page: 1,
        limit: 10,
        loading: false,
        hasMore: true,
        search: '',
    };

    /**
     * Mereset pagination dan memuat ulang data dari server.
     * Digunakan saat search query berubah atau dropdown dibuka.
     */
    function resetAndFetch() {
        state.page = 1;
        state.hasMore = true;
        state.loading = false;
        fetchItems(state, list, false);
    }

    // Toggle menu dropdown
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('hidden');

        // Reset & fetch saat dropdown dibuka
        if (!menu.classList.contains('hidden')) {
            // Fokus ke search input
            if (searchInput) {
                setTimeout(function () { searchInput.focus(); }, 50);
            }

            // Selalu muat ulang saat dropdown dibuka
            resetAndFetch();
        }
    });

    // ─── Search Input dengan Debounce ──────────────────────────────
    if (searchInput) {
        /**
         * Handler debounce untuk pencarian barang.
         * Menunggu 300ms setelah user berhenti mengetik sebelum fetch.
         */
        var debouncedSearch = debounce(function () {
            var query = searchInput.value.trim();
            if (query !== state.search) {
                state.search = query;
                resetAndFetch();
            }
        }, 300);

        searchInput.addEventListener('input', function () {
            debouncedSearch();
        });

        // Cegah dropdown tertutup saat klik di search input
        searchInput.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // Clear selection (reset ke "Semua Barang")
    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            // Reset search input
            if (searchInput) {
                searchInput.value = '';
            }
            state.search = '';
            setSelected(hiddenInput, label, menu, '', '- Semua Barang -');
        });
    }

    // Infinite scroll: load more saat scroll mendekati bawah
    list.addEventListener('scroll', function () {
        var nearBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 20;
        if (nearBottom && !state.loading && state.hasMore) {
            fetchItems(state, list, true);
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
 * Menginisialisasi auto-submit pada input tanggal.
 * Saat user mengubah tanggal mulai atau tanggal akhir,
 * form akan otomatis disubmit untuk memperbarui laporan.
 */
function initDateFilter() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = startDate?.closest('form');

    if (!form) return;

    /**
     * Menangani perubahan pada input tanggal.
     * Memastikan tanggal akhir tidak sebelum tanggal mulai,
     * lalu mensubmit form.
     */
    function handleDateChange() {
        const start = startDate.value;
        const end = endDate.value;

        // Validasi sisi client: tanggal akhir harus >= tanggal mulai
        if (start && end && end < start) {
            endDate.value = start;
        }

        form.submit();
    }

    if (startDate) {
        startDate.addEventListener('change', handleDateChange);
    }

    if (endDate) {
        endDate.addEventListener('change', handleDateChange);
    }
}

// ─── Inisialisasi ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initItemDropdown();
    initDateFilter();
});
