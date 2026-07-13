/**
 * Currency Helpers
 * Shared utilities for parsing and formatting Indonesian Rupiah currency values.
 */

function parseCurrencyInput(value) {
    const rawValue = String(value ?? '').trim();

    if (!rawValue) {
        return 0;
    }

    return parseInt(rawValue.replace(/[^0-9-]/g, ''), 10) || 0;
}

function formatRupiah(value) {
    return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
}

window.parseCurrencyInput = parseCurrencyInput;
window.formatRupiah = formatRupiah;
