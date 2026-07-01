{{-- Modal Delete Invoice Proyek --}}
<div id="deleteModal-{{ $invoice->invoice_number }}"
    class="hidden fixed inset-0 z-50 bg-surface-overlay items-center justify-center px-4">
    <div class="bg-surface-base rounded-xl shadow-lg w-full max-w-md p-6 relative max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-error">
                <i class="fa-solid fa-triangle-exclamation"></i> Konfirmasi Hapus
            </h2>
            <button type="button" class="text-text-secondary hover:text-text-primary"
                onclick="closeModal('deleteModal-{{ $invoice->invoice_number }}')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <div class="text-center mb-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-error-light mb-3">
                <i class="fa-solid fa-trash-can text-3xl text-error"></i>
            </div>
            <h4 class="text-lg font-bold text-text-heading mb-2">Apakah Anda yakin?</h4>
            <p class="text-text-label">
                Data {{ auth()->user()->isAdmin() ? 'invoice' : 'invoice proyek' }} ini akan dihapus secara permanen dan tidak dapat dikembalikan.
            </p>
        </div>

        <div class="bg-surface-secondary p-4 rounded-lg border border-border mb-4">
            <table class="w-full text-sm">
                <tr>
                    <td class="font-semibold text-text-primary py-1">No Invoice:</td>
                    <td class="text-text-primary py-1">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="font-semibold text-text-primary py-1">Tanggal:</td>
                    <td class="text-text-primary py-1">{{ $invoice->invoice_date->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="font-semibold text-text-primary py-1">Kepada:</td>
                    <td class="text-text-primary py-1">{{ $invoice->recipient }}</td>
                </tr>
                <tr>
                    <td class="font-semibold text-text-primary py-1">Total:</td>
                    <td class="text-error font-bold py-1">
                        Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" class="bg-button-cancel px-4 py-2 rounded hover:bg-button-cancel-hover"
                onclick="closeModal('deleteModal-{{ $invoice->invoice_number }}')">
                <i class="fa-solid fa-times"></i> Batal
            </button>
            <button type="button" class="bg-error hover:bg-error-hover text-white px-4 py-2 rounded"
                onclick="deleteInvoiceProyek('{{ $invoice->invoice_number }}')">
                <i class="fa-solid fa-trash-can"></i> Ya, Hapus
            </button>
        </div>

        {{-- Hidden form untuk submit --}}
        <form id="deleteForm-{{ $invoice->invoice_number }}" action="{{ route('proyek-invoice.destroySelected') }}"
            method="POST" style="display: none;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="selected_invoices[]" value="{{ $invoice->invoice_number }}">
        </form>
    </div>
</div>
