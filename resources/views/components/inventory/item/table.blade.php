{{-- Item Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('items.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">ID Barang</th>
                            <th class="p-2 text-left">Nama Barang</th>
                            <th class="p-2 text-left">Jumlah</th>
                            <th class="p-2 text-center">Harga Modal</th>
                            <th class="p-2 text-center">Harga Jual</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_items[]" value="{{ $item->id_item }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>
                                <td class="p-2 font-medium text-primary">{{ $item->id_item }}</td>
                                <td class="p-2">{{ $item->name_item }}</td>
                                <td class="p-2 text-center">{{ $item->quantity }}</td>

                                {{-- Harga Modal --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($item->capital_price, 0, ',', '.') }}
                                </td>

                                {{-- Harga Jual --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($item->selling_price, 0, ',', '.') }}
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <x-buttons.edit onclick="openModal('editModal-{{ $item->id_item }}')" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center p-4 text-text-secondary">
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