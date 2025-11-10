{{-- Item Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('items.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
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
                            <tr class="border-t hover:bg-gray-50">
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
                                        <button type="button" onclick="openModal('editModal-{{ $item->id_item }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Barang">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center p-4 text-gray-500">
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
