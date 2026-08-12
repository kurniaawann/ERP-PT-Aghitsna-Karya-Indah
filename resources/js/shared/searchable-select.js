/**
 * Searchable Select - Menginisialisasi dropdown yang dapat dicari yang dibuat
 * oleh komponen Blade <x-forms.searchable-select>.
 *
 * Perilaku: dropdown hanya menampilkan 10 opsi awal; saat melakukan scroll
 * ke bawah pada dropdown, 10 opsi berikutnya dimuat (infinite scroll).
 * Pencarian memfilter opsi yang cocok.
 *
 * Penggunaan: panggil initSearchableSelects() setelah DOM siap atau setelah modal terbuka.
 */

const SEARCHABLE_SELECT_PAGE_SIZE = 10;

/**
 * Menginisialisasi seluruh komponen searchable select dalam container
 * (atau seluruh dokumen bila container kosong).
 *
 * Alur:
 * 1. Kumpulkan semua wrapper `.searchable-select-wrapper` dari container.
 * 2. Untuk setiap wrapper yang belum diinisialisasi (dataset.searchableInitialized
 *    != 'true'), tandai sebagai sudah diinisialisasi.
 * 3. Ambil elemen penting wrapper: input pencarian, dropdown, opsi
 *    (.searchable-option), pesan "no results", dan hidden input penyimpan nilai.
 * 4. Bila input pencarian/dropdown tidak ada, lewati wrapper ini.
 * 5. Registrasi event:
 *    - focus input -> tampilkan dropdown & terapkan filter.
 *    - input pencarian -> saring opsi berdasarkan dataset.search dan terapkan
 *      batas tampilan (10 awal), tampilkan/sembunyikan opsi dan "no results".
 *    - scroll dropdown -> bila mendekati dasar, tambah 10 opsi berikutnya.
 *    - klik opsi -> isi searchInput dengan label, hiddenInput dengan value,
 *      lalu tutup dropdown (pilih satu nilai saja).
 *    - klik di luar wrapper -> tutup dropdown.
 *
 * @param  {HTMLElement|Document}  [container]  Elemen pencarian; default document.
 * @returns {void}
 */
function initSearchableSelects(container) {
    const wrappers = (container || document).querySelectorAll('.searchable-select-wrapper');

    wrappers.forEach(function(wrapper) {
        // Lewati yang sudah diinisialisasi
        if (wrapper.dataset.searchableInitialized === 'true') return;
        wrapper.dataset.searchableInitialized = 'true';

        const searchInput = wrapper.querySelector('.searchable-select-input');
        const dropdown = wrapper.querySelector('.searchable-dropdown');
        const optionsDiv = wrapper.querySelector('.searchable-options');
        const noResults = wrapper.querySelector('.searchable-no-results');
        const hiddenInput = wrapper.querySelector('.searchable-select-hidden');

        if (!searchInput || !dropdown) return;

        let visibleLimit = SEARCHABLE_SELECT_PAGE_SIZE;
        let searchTerm = '';

        function getOptions() {
            return wrapper.querySelectorAll('.searchable-option');
        }

        // Terapkan filter pencarian + batas tampilan (10 awal).
        function applyFilter() {
            let matchIndex = 0;
            let hasResults = false;

            getOptions().forEach(function(option) {
                // Opsi placeholder ("-- Pilih ... --") selalu tampil di atas.
                if (option.dataset.value === '') {
                    option.style.display = 'block';
                    return;
                }

                const matches = (option.dataset.search || '').includes(searchTerm);
                if (matches) {
                    hasResults = true;
                    option.style.display = (matchIndex < visibleLimit) ? 'block' : 'none';
                    matchIndex++;
                } else {
                    option.style.display = 'none';
                }
            });

            if (optionsDiv) optionsDiv.classList.toggle('hidden', !hasResults);
            if (noResults) noResults.classList.toggle('hidden', hasResults);
        }

        // Tampilkan dropdown saat fokus
        searchInput.addEventListener('focus', function() {
            dropdown.classList.remove('hidden');
            applyFilter();
        });

        // Fungsi pencarian
        searchInput.addEventListener('input', function() {
            searchTerm = this.value.toLowerCase();
            visibleLimit = SEARCHABLE_SELECT_PAGE_SIZE; // reset ke 10 awal saat cari baru
            applyFilter();
        });

        // Infinite scroll: muat 10 opsi berikutnya saat mendekati dasar dropdown
        dropdown.addEventListener('scroll', function() {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 5) {
                visibleLimit += SEARCHABLE_SELECT_PAGE_SIZE;
                applyFilter();
            }
        });

        // Menangani pemilihan opsi
        getOptions().forEach(function(option) {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const label = this.dataset.label;

                searchInput.value = label || '';
                hiddenInput.value = value || '';
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

                dropdown.classList.add('hidden');
            });
        });

        // Tutup dropdown saat mengklik di luar
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    });
}


/**
 * Ekspos initSearchableSelects ke global window agar bisa dipanggil manual
 * setelah modal terbuka / konten di-render dinamis.
 *
 * @returns {void}
 */
window.initSearchableSelects = initSearchableSelects;

/**
 * Inisialisasi otomatis saat DOM siap.
 *
 * Jika dokumen masih loading, tunggu event DOMContentLoaded; jika sudah siap,
 * langsung jalankan agar komponen yang sudah dirender langsung berfungsi.
 *
 * @returns {void}
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initSearchableSelects();
    });
} else {
    initSearchableSelects();
}
