{{-- Modal Delete Invoice Proyek --}}
<div id="deleteModal-{{ $invoice->invoice_number }}"
    class="hidden fixed inset-0 z-50 bg-gray-900/60 items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-red-600">
                <i class="fa-solid fa-triangle-exclamation"></i> Konfirmasi Hapus
            </h2>
            <button type="button" class="text-gray-500 hover:text-gray-700"
                onclick="closeModal('deleteModal-{{ $invoice->invoice_number }}')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <div class="text-center mb-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-3">
                <i class="fa-solid fa-trash-can text-3xl text-red-600"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-800 mb-2">Apakah Anda yakin?</h4>
            <p class="text-gray-600">
                Data invoice proyek ini akan dihapus secara permanen dan tidak dapat dikembalikan.
            </p>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
            <table class="w-full text-sm">
                <tr>
                    <td class="font-semibold text-gray-700 py-1">No Invoice:</td>
                    <td class="text-gray-900 py-1">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="font-semibold text-gray-700 py-1">Tanggal:</td>
                    <td class="text-gray-900 py-1">{{ $invoice->invoice_date->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="font-semibold text-gray-700 py-1">Kepada:</td>
                    <td class="text-gray-900 py-1">{{ $invoice->recipient }}</td>
                </tr>
                <tr>
                    <td class="font-semibold text-gray-700 py-1">Total:</td>
                    <td class="text-red-600 font-bold py-1">
                        Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300"
                onclick="closeModal('deleteModal-{{ $invoice->invoice_number }}')">
                <i class="fa-solid fa-times"></i> Batal
            </button>
            <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
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
