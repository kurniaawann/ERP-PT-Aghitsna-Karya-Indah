/**
 * Quick Add Rekening Pembayaran - Shared Module
 *
 * Menangani submit AJAX dari modal "Tambah Rekening Pembayaran" yang
 * dirender oleh komponen <x-finance.quick-add-payment-account>.
 *
 * Setelah rekening baru berhasil dibuat, rekening otomatis disisipkan ke
 * target form yang sama tempat tombol berada:
 *  - Mode "select"   : opsi baru ditambahkan ke
 *                      <select name="payment_account_id"> dan dipilih.
 *  - Mode "checkbox" : checkbox baru disisipkan ke dalam grup rekening dan
 *                      langsung dicentang. Struktur item di-clone dari
 *                      checkbox pertama yang sudah ada agar konsisten
 *                      dengan tampilan modul.
 */

const QUICK_ADD_PREFIX = 'quickAddPaymentAccountModal-';
const QUICK_ADD_BTN_PREFIX = 'quickAddPaymentAccountBtn-';

/**
 * Baca konfigurasi quick-add dari hidden input di dalam modal.
 */
function readConfig(modal) {
    const getValue = (name) => {
        const el = modal.querySelector('[name="' + name + '"]');
        return el ? el.value : null;
    };
    return {
        url: getValue('quick_add_url'),
        mode: getValue('quick_add_mode') || 'checkbox',
        target: getValue('quick_add_target') || null,
        container: getValue('quick_add_container') || null,
    };
}

/**
 * Temukan tombol "+" yang membuka modal quick-add.
 */
function findButton(modal) {
    const uid = modal.id.replace(QUICK_ADD_PREFIX, '');
    return document.getElementById(QUICK_ADD_BTN_PREFIX + uid) || null;
}

/**
 * Temukan <select name="payment_account_id"> tempat rekening disisipkan.
 * Prioritas: selector template di dalam form induk → select di form induk.
 */
function findTargetSelect(button, config) {
    const parentForm = button ? button.closest('form') : null;
    if (parentForm) {
        const target = config.target
            ? parentForm.querySelector(config.target)
            : parentForm.querySelector('select[name="payment_account_id"]');
        if (target) return target;
    }
    if (config.target) {
        const target = document.querySelector(config.target);
        if (target) return target;
    }
    return document.querySelector('select[name="payment_account_id"]') || null;
}

/**
 * Temukan container grup checkbox rekening.
 * Prioritas: selector container → section terdekat → `.space-y-2` form induk.
 */
function findCheckboxContainer(button, config) {
    let container = null;
    if (config.container) {
        const el = document.querySelector(config.container);
        if (el) container = el;
    }
    if (!container && button) {
        // Section container = parent dari baris header (tempat tombol berada),
        // lalu cari grup checkbox `.space-y-2` di dalam section tersebut.
        const section = button.parentElement && button.parentElement.parentElement
            ? button.parentElement.parentElement
            : null;
        if (section) container = section.querySelector('.space-y-2') || null;
    }
    if (!container && button) {
        const parentForm = button.closest('form');
        if (parentForm) container = parentForm.querySelector('.space-y-2') || null;
    }
    if (!container && button) {
        container = button.closest('form') || null;
    }
    return container;
}

/**
 * Bangun kartu rekening dari nol (fallback saat tidak ada template/clone).
 */
function buildCardFromScratch(account) {
    const label = document.createElement('label');
    label.className = 'flex items-start p-2 bg-white rounded border hover:bg-surface-secondary cursor-pointer';
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.name = 'selected_payment_accounts[]';
    input.value = account.id;
    input.checked = true;
    input.className = 'mt-1 mr-3 accent-primary';
    input.id = 'paymentAccount' + account.id;
    const wrapper = document.createElement('div');
    wrapper.className = 'flex-1';
    const bankEl = document.createElement('div');
    bankEl.className = 'font-semibold text-text-heading';
    bankEl.textContent = account.bank_name;
    const infoEl = document.createElement('div');
    infoEl.className = 'text-sm text-text-label';
    infoEl.textContent = 'No: ' + account.account_number + ' a/n ' + account.account_holder;
    wrapper.appendChild(bankEl);
    wrapper.appendChild(infoEl);
    label.appendChild(input);
    label.appendChild(wrapper);
    return label;
}

/**
 * Clone item rekening pertama yang ada lalu ganti isinya dengan rekening baru.
 * Mengembalikan null bila tidak ada template.
 */
function cloneCheckboxRow(container, account) {
    const existing = container.querySelector('[name="selected_payment_accounts[]"]');
    if (!existing) return null;

    const template = existing.closest('label') || existing.closest('div');
    if (!template) return null;

    const clone = template.cloneNode(true);
    const input = clone.querySelector('[name="selected_payment_accounts[]"]');
    if (!input) return null;

    input.value = account.id;
    input.checked = true;
    const newInputId = 'paymentAccount' + account.id;
    input.id = newInputId;

    if (template.tagName === 'LABEL') {
        // Gaya kartu: nama bank + info rekening di dalam label.
        const bankEl = clone.querySelector('.font-semibold');
        if (bankEl) bankEl.textContent = account.bank_name;
        const infoEl = clone.querySelector('.text-sm');
        if (infoEl) infoEl.textContent = 'No: ' + account.account_number + ' a/n ' + account.account_holder;
    } else {
        // Gaya sederhana (RAB): input berlabel sibling di dalam satu div.
        const lbl = clone.querySelector('label');
        if (lbl) {
            lbl.setAttribute('for', newInputId);
            lbl.innerHTML = '<span class="font-medium">' + account.bank_name + '</span> - ' + account.account_number;
        }
    }

    return clone;
}

/**
 * Tagih elemen "rekening kosong" di dalam container bila ada.
 */
function removeEmptyState(container) {
    const empty = container.querySelector('.bg-yellow-100');
    if (empty) empty.remove();
}

/**
 * Sisipkan rekening baru ke container checkbox (mode checkbox).
 */
function injectCheckbox(button, config, account) {
    const container = findCheckboxContainer(button, config);
    if (!container) return;

    removeEmptyState(container);

    let row = cloneCheckboxRow(container, account);
    if (!row) row = buildCardFromScratch(account);

    container.appendChild(row);
    row.querySelectorAll('[name="selected_payment_accounts[]"]').forEach((input) => {
        const event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
    });
}

/**
 * Sisipkan rekening baru ke <select> (mode select) dan langsung pilih.
 */
function injectSelect(button, config, account) {
    const select = findTargetSelect(button, config);
    if (!select) return;

    const option = document.createElement('option');
    option.value = account.id;
    option.textContent = account.bank_name + ' - ' + account.account_number;
    select.appendChild(option);
    select.value = account.id;
    select.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Ambil token CSRF dari input `_token` atau meta `csrf-token` di halaman.
 */
function getCsrfToken() {
    const input = document.querySelector('input[name="_token"]');
    if (input && input.value) return input.value;
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Validasi manual & kirim data rekening baru via AJAX.
 */
function submitQuickAdd(modal, button) {
    const config = readConfig(modal);
    const fields = Array.from(modal.querySelectorAll('.quick-add-acc-field'));

    // Validasi sederhana: semua field wajib terisi.
    for (const field of fields) {
        if (!field.value.trim()) {
            field.focus();
            if (typeof window.showToast === 'function') {
                window.showToast((field.name === 'bank_name' ? 'Nama bank'
                    : field.name === 'account_number' ? 'Nomor rekening'
                    : 'Nama pemilik rekening') + ' wajib diisi.', 'error');
            }
            return;
        }
    }

    const submitBtn = button;
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';

    const formData = new FormData();
    fields.forEach((field) => formData.append(field.name, field.value.trim()));
    const token = getCsrfToken();
    if (token) formData.append('_token', token);

    axios.post(config.url, formData)
        .then((response) => {
            const data = response.data;
            if (data.success && data.account) {
                if (config.mode === 'select') {
                    injectSelect(button, config, data.account);
                } else {
                    injectCheckbox(button, config, data.account);
                }

                fields.forEach((field) => { field.value = ''; });
                if (typeof closeModal === 'function') closeModal(modal.id);
                if (typeof window.showToast === 'function') {
                    window.showToast(data.message || 'Rekening pembayaran berhasil ditambahkan!', 'success');
                }
            } else if (typeof window.showToast === 'function') {
                window.showToast(data.message || 'Gagal menambahkan rekening pembayaran.', 'error');
            }
        })
        .catch((error) => {
            let msg = 'Terjadi kesalahan saat menyimpan rekening pembayaran.';
            if (error.response && error.response.data && error.response.data.errors) {
                const errors = Object.values(error.response.data.errors).flat();
                msg = errors[0] || msg;
            } else if (error.response && error.response.data && error.response.data.message) {
                msg = error.response.data.message;
            }
            if (typeof window.showToast === 'function') {
                window.showToast(msg, 'error');
            }
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
}

/**
 * Inisialisasi bind submit AJAX untuk semua modal quick-add.
 */
function initQuickAddPaymentAccount() {
    document.querySelectorAll('[id^="' + QUICK_ADD_PREFIX + '"]').forEach((modal) => {
        const submitBtn = modal.querySelector('[data-quick-add-submit]');
        if (!submitBtn || submitBtn.dataset.quickAddBound) return;
        submitBtn.dataset.quickAddBound = '1';

        submitBtn.addEventListener('click', function () {
            submitQuickAdd(modal, this);
        });

        modal.querySelectorAll('.quick-add-acc-field').forEach((field) => {
            field.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitQuickAdd(modal, submitBtn);
                }
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', initQuickAddPaymentAccount);