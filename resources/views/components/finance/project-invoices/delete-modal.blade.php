{{-- Modal Hapus Invoice Proyek (gaya Filament, satu per invoice).
     Dibuka dari menu Aksi ⋮ "Hapus": openModal('deleteModal-{invoice_number}').
     Konfirmasi → deleteInvoiceProyek(no) (project-invoices/index.js) yang
     men-submit form#deleteForm-{no} ke route('proyek-invoice.destroySelected').

     Nama input memakai [0] (bukan []) agar tidak ikut tertangkap selector
     input[name="selected_invoices[]"] milik checkbox tabel (select all &
     status tombol hapus massal). PHP tetap membacanya sebagai array. --}}
@props(['invoice'])

<x-fi.modal id="deleteModal-{{ $invoice->invoice_number }}" heading="Apakah Anda yakin?" icon="trash" color="danger" width="md"
    onConfirm="deleteInvoiceProyek('{{ addslashes($invoice->invoice_number) }}')" submitLabel="Ya, Hapus">
    Data {{ auth()->user()->isAdmin() ? 'invoice' : 'invoice proyek' }} ini akan dihapus secara permanen dan tidak dapat dikembalikan.

    <dl class="mt-4 grid gap-y-2 rounded-lg bg-gray-50 p-4 text-start text-sm ring-1 ring-inset ring-gray-950/5">
        <div class="flex justify-between gap-3">
            <dt class="text-gray-500">No Invoice</dt>
            <dd class="font-medium text-gray-950">{{ $invoice->invoice_number }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-gray-500">Tanggal</dt>
            <dd class="text-gray-950">{{ $invoice->invoice_date->format('d F Y') }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-gray-500">Kepada</dt>
            <dd class="text-end text-gray-950">{{ $invoice->recipient }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-gray-500">Total</dt>
            <dd class="font-semibold tabular-nums text-red-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</dd>
        </div>
    </dl>

    {{-- Hidden form untuk submit --}}
    <form id="deleteForm-{{ $invoice->invoice_number }}" action="{{ route('proyek-invoice.destroySelected') }}"
        method="POST" class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" name="selected_invoices[0]" value="{{ $invoice->invoice_number }}">
    </form>
</x-fi.modal>
