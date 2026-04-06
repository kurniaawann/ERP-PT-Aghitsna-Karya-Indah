{{-- Stock In Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('stock-ins.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">ID Barang Masuk</th>
                            <th class="p-2 text-left">ID Barang</th>
                            <th class="p-2 text-left">Nama Barang</th>
                            <th class="p-2 text-center">Jumlah</th>
                            <th class="p-2 text-right">Harga Modal</th>
                            <th class="p-2 text-right">Total</th>
                            <th class="p-2 text-left">Tanggal</th>
                            <th class="p-2 text-left">Keterangan</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockIns as $record)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_stock_ins[]"
                                        value="{{ $record->id_stock_in }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>
                                <td class="p-2 font-medium text-primary">{{ $record->id_stock_in }}</td>
                                <td class="p-2">{{ $record->id_item }}</td>
                                <td class="p-2">{{ $record->item->name_item ?? '-' }}</td>
                                <td class="p-2 text-center">{{ $record->quantity }}</td>
                                <td class="p-2 text-right">Rp
                                    {{ number_format($record->capital_price, 0, ',', '.') }}
                                </td>
                                <td class="p-2 text-right font-semibold">Rp
                                    {{ number_format($record->total_capital, 0, ',', '.') }}</td>
                                <td class="p-2">{{ $record->tanggal->format('d M Y') }}</td>
                                <td class="p-2 max-w-xs truncate" title="{{ $record->keterangan ?? '-' }}">
                                    {{ $record->keterangan ?? '-' }}
                                </td>
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                            onclick="openModal('editModal-{{ $record->id_stock_in }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors text-xs">
                                            <i class="fa-solid fa-pen w-3 h-3"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center p-4 text-text-secondary">
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
