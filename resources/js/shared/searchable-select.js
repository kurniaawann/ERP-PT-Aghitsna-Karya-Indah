/**
 * Searchable Select - Menginisialisasi dropdown yang dapat dicari yang dibuat
 * oleh komponen Blade <x-forms.searchable-select>.
 *
 * Penggunaan: panggil initSearchableSelects() setelah DOM siap atau setelah modal terbuka.
 */

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
 *    - focus input -> tampilkan dropdown.
 *    - input pencarian -> saring opsi berdasarkan dataset.search; tampilkan/
 *      sembunyikan opsi dan pesan "no results" sesuai hasil pencarian.
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
        const options = wrapper.querySelectorAll('.searchable-option');
        const noResults = wrapper.querySelector('.searchable-no-results');
        const hiddenInput = wrapper.querySelector('.searchable-select-hidden');

        if (!searchInput || !dropdown) return;

        // Tampilkan dropdown saat fokus
        searchInput.addEventListener('focus', function() {
            dropdown.classList.remove('hidden');
        });

        // Fungsi pencarian
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let hasResults = false;

            options.forEach(function(option) {
                const searchText = option.dataset.search || '';
                if (searchText.includes(searchTerm)) {
                    option.style.display = 'block';
                    hasResults = true;
                } else {
                    option.style.display = 'none';
                }
            });

            const optionsDiv = wrapper.querySelector('.searchable-options');
            if (hasResults) {
                noResults.classList.add('hidden');
                optionsDiv.classList.remove('hidden');
            } else {
                noResults.classList.remove('hidden');
                optionsDiv.classList.add('hidden');
            }
        });

        // Menangani pemilihan opsi
        options.forEach(function(option) {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const label = this.dataset.label;

                searchInput.value = label || '';
                hiddenInput.value = value || '';

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
