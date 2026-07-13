{{-- Invoice Barang Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('item-invoice.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No Invoice</th>
                            <th class="p-2 text-left">Tanggal</th>
                            <th class="p-2 text-left">Kepada</th>
                            <th class="p-2 text-left">Keterangan</th>
                            <th class="p-2 text-center">Total</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_invoices[]"
                                        value="{{ $invoice->invoice_number }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>
                                <td class="p-2 font-medium text-primary">{{ $invoice->invoice_number }}</td>
                                <td class="p-2">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                                <td class="p-2">{{ $invoice->recipient }}</td>
                                <td class="p-2 text-text-label">
                                    {{ $invoice->project_description ? \Illuminate\Support\Str::limit($invoice->project_description, 35) : '-' }}
                                </td>
                                <td class="p-2 text-right font-medium">Rp
                                    {{ number_format($invoice->getNetAmount(), 0, ',', '.') }}</td>
                                <td class="p-2 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $invoice->status_badge_class }}">
                                        {{ $invoice->status_label }}
                                    </span>
                                </td>
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-1 flex-wrap">
                                        <button type="button"
                                            onclick="openModal('detailModal-{{ $invoice->invoice_number }}')"
                                            class="flex items-center gap-1 bg-info hover:bg-info/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Lihat Detail">
                                            <i class="fa-solid fa-eye w-3 h-3"></i>
                                            Lihat
                                        </button>

                                        <button type="button"
                                            onclick="openModal('editModal-{{ $invoice->invoice_number }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Invoice">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>

                                        <a href="{{ route('item-invoice.print.pdf', $invoice->invoice_number) }}"
                                            class="flex items-center gap-1 bg-error hover:bg-error/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Print PDF">
                                            <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                            PDF
                                        </a>

                                        <a href="{{ route('item-invoice.print.excel', $invoice->invoice_number) }}"
                                            class="flex items-center gap-1 bg-success hover:bg-success/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Print Excel">
                                            <i class="fa-solid fa-file-excel w-3 h-3"></i>
                                            Excel
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center p-4 text-text-secondary">Data invoice item tidak
                                    ditemukan.</td>
                            </tr>
                        @endforelse

                        @if ($invoices->isNotEmpty())
                            <tr
                                class="bg-gradient-to-r from-primary/20 to-primary/10 border-t-4 border-primary font-bold text-base">
                                <td colspan="5" class="p-3 text-right text-text-heading">TOTAL INVOICE BARANG</td>
                                <td class="p-3 text-right text-text-heading">Rp
                                    {{ number_format($invoices->sum(fn($invoice) => $invoice->getNetAmount()), 0, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
