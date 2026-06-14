{{-- Purchase Invoice Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('purchase-invoice.destroy-selected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">Tanggal</th>
                            <th class="p-2 text-left">Nama Material</th>
                            <th class="p-2 text-left">NPWP</th>
                            <th class="p-2 text-left">Nama Barang</th>
                            <th class="p-2 text-right">Harga Jual</th>
                            <th class="p-2 text-right">PPN Pajak</th>
                            <th class="p-2 text-right">Total</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_invoices[]" value="{{ $invoice->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 text-sm">{{ $invoice->date->format('d-m-Y') }}</td>
                                <td class="p-2">{{ $invoice->material_name }}</td>
                                <td class="p-2 text-sm text-text-label">{{ $invoice->npwp }}</td>
                                <td class="p-2 font-medium">{{ $invoice->item_name }}</td>

                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($invoice->selling_price, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($invoice->ppn_tax, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($invoice->selling_price + $invoice->ppn_tax, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-1 flex-wrap">
                                        <x-buttons.edit onclick="openModal('editModal-{{ $invoice->id }}')" />

                                        {{-- PDF action removed as requested --}}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center p-4 text-text-secondary">
                                    Data faktur pembelian tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>