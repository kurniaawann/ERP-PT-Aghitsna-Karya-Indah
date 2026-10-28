/**
 * DO Semen - Halaman Form Tambah/Edit JavaScript
 *
 * Modul ini menangani:
 * - Baris Data Semen dinamis (tambah dari <template>, hapus baris)
 * - Format input currency Rupiah (harga modal & harga tiap baris semen)
 * - Total per baris, total volume/subtotal (tfoot) & ringkasan estimasi profit
 * - Loading state saat submit (anti double-submit)
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('cement-do-form');
    const tbody = document.getElementById('cement-rows');
    const template = document.getElementById('cement-row-template');
    if (!form || !tbody || !template) return;

    const hargaModalInput = document.getElementById('harga-modal');
    const emptyState = document.getElementById('cement-rows-empty');
    const profitWarning = document.getElementById('profit-warning');

    // Indeks cements[..] selalu naik (tidak memakai jumlah baris) agar baris
    // baru tidak menimpa indeks baris lain setelah ada baris yang dihapus.
    let nextIndex = tbody.querySelectorAll('.cement-row').length;

    /**
     * Isi semua elemen ringkasan dengan kunci tertentu
     * (mis. volume tampil di kartu Ringkasan & tfoot tabel).
     *
     * @param {string} key   Nilai atribut data-summary.
     * @param {string} text
     * @returns {NodeListOf<HTMLElement>}
     */
    function setSummary(key, text) {
        const elements = document.querySelectorAll('[data-summary="' + key + '"]');
        elements.forEach(function (el) {
            el.textContent = text;
        });
        return elements;
    }

    /**
     * Hitung ulang total tiap baris, total volume, subtotal, dan estimasi profit.
     *
     * @returns {void}
     */
    function recalculate() {
        const rows = tbody.querySelectorAll('.cement-row');
        let volume = 0;
        let subtotal = 0;

        rows.forEach(function (tr) {
            const jumlah = parseInt(tr.querySelector('.cement-jumlah').value, 10) || 0;
            const harga = parseCurrencyInput(tr.querySelector('.cement-harga').value);
            const total = jumlah * harga;

            tr.querySelector('.cement-total').textContent = formatRupiah(total);
            volume += jumlah;
            subtotal += total;
        });

        const hargaModal = hargaModalInput ? parseCurrencyInput(hargaModalInput.value) : 0;
        const profit = subtotal - hargaModal;

        setSummary('rows', rows.length.toLocaleString('id-ID'));
        setSummary('volume', volume.toLocaleString('id-ID') + ' zak');
        setSummary('subtotal', formatRupiah(subtotal));
        setSummary('modal', formatRupiah(hargaModal));
        setSummary('profit', (profit < 0 ? '− ' : '') + formatRupiah(Math.abs(profit))).forEach(function (el) {
            el.classList.toggle('text-emerald-600', profit >= 0);
            el.classList.toggle('text-red-600', profit < 0);
        });

        if (profitWarning) profitWarning.classList.toggle('hidden', profit >= 0);
        if (emptyState) emptyState.classList.toggle('hidden', rows.length > 0);
    }

    /**
     * Tambahkan satu baris Data Semen dari <template id="cement-row-template">
     * lalu fokus ke input pertama yang wajib diisi.
     *
     * @returns {void}
     */
    function addRow() {
        const fragment = template.content.cloneNode(true);
        const index = nextIndex++;

        fragment.querySelectorAll('[name*="[IDX]"]').forEach(function (input) {
            input.name = input.name.replace('[IDX]', '[' + index + ']');
        });

        const tr = fragment.querySelector('.cement-row');
        tbody.appendChild(fragment);
        recalculate();

        const firstInput = tr.querySelector('[name$="[nama_proyek]"]');
        if (firstInput) firstInput.focus();
    }

    // ─── Tambah & Hapus Baris ────────────────────────────────────────
    document.querySelectorAll('[data-add-cement-row]').forEach(function (btn) {
        btn.addEventListener('click', addRow);
    });

    tbody.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-row-btn');
        if (!removeBtn) return;

        removeBtn.closest('.cement-row').remove();
        recalculate();
    });

    // ─── Format Harga & Hitung Ulang Saat Input Berubah ──────────────
    form.addEventListener('input', function (e) {
        const input = e.target;
        if (input === hargaModalInput || input.classList.contains('cement-harga')) {
            input.value = formatRupiah(parseCurrencyInput(input.value));
        }

        if (input === hargaModalInput || input.closest('.cement-row')) {
            recalculate();
        }
    });

    // ─── Anti Double-Submit ──────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn, submitBtn.innerHTML)) {
            e.preventDefault();
            return false;
        }
    });

    recalculate();
});
