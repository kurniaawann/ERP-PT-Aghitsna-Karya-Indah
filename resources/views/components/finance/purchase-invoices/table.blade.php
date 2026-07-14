{{-- ==================== Tabel Faktur Pembelian ==================== --}}
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
                                {{-- Checkbox --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_invoices[]" value="{{ $invoice->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                {{-- Tanggal --}}
                                <td class="p-2 text-sm">{{ $invoice->date->format('d-m-Y') }}</td>

                                {{-- Nama Material --}}
                                <td class="p-2">{{ $invoice->material_name }}</td>

                                {{-- NPWP --}}
                                <td class="p-2 text-sm text-text-label">{{ $invoice->npwp }}</td>

                                {{-- Nama Barang --}}
                                <td class="p-2 font-medium">{{ $invoice->item_name }}</td>

                                {{-- Harga Jual --}}
                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($invoice->selling_price, 0, ',', '.') }}
                                </td>

                                {{-- PPN Pajak --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($invoice->ppn_tax, 0, ',', '.') }}
                                </td>

                                {{-- Total --}}
                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($invoice->selling_price + $invoice->ppn_tax, 0, ',', '.') }}
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-1 flex-wrap">
                                        <button type="button" onclick="openModal('editModal-{{ $invoice->id }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Faktur">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
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
