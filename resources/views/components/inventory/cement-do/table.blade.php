{{-- Table DO Semen --}}
<form id="deleteForm" method="POST" action="{{ route('cement-does.destroySelected') }}">
    @csrf
    @method('DELETE')

    {{-- Tabel Utama --}}
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">

                    {{-- Header Tabel --}}
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No</th>
                            <th class="p-2 text-left">Tanggal</th>
                            <th class="p-2 text-left">Proyek</th>
                            <th class="p-2 text-center">Volume</th>
                            <th class="p-2 text-center">Satuan</th>
                            <th class="p-2 text-right">Harga</th>
                            <th class="p-2 text-right">Jumlah</th>
                            <th class="p-2 text-left">Tgl Lunas</th>
                            <th class="p-2 text-right">Harga Modal</th>
                            <th class="p-2 text-right">Profit</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>

                    {{-- Body Tabel --}}
                    <tbody>
                        @forelse($cementDeliveryOrders as $cementDeliveryOrder)
                            <tr class="border-t hover:bg-surface-secondary">

                                {{-- Checkbox --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_items[]" value="{{ $cementDeliveryOrder->no }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                {{-- No --}}
                                <td class="p-2 font-medium text-primary">{{ $cementDeliveryOrder->no }}</td>

                                {{-- Tanggal --}}
                                <td class="p-2">{{ $cementDeliveryOrder->tanggal?->format('d M Y') ?: '-' }}</td>

                                {{-- Proyek --}}
                                <td class="p-2">{{ $cementDeliveryOrder->proyek }}</td>

                                {{-- Volume --}}
                                <td class="p-2 text-center">{{ number_format($cementDeliveryOrder->volume, 0, ',', '.') }}</td>

                                {{-- Satuan --}}
                                <td class="p-2 text-center">{{ $cementDeliveryOrder->satuan ?: '-' }}</td>

                                {{-- Harga --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->harga, 0, ',', '.') }}
                                </td>

                                {{-- Jumlah --}}
                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->jumlah, 0, ',', '.') }}
                                </td>

                                {{-- Tanggal Lunas --}}
                                <td class="p-2">{{ $cementDeliveryOrder->tanggal_lunas?->format('d M Y') ?: '-' }}</td>

                                {{-- Harga Modal --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->harga_modal, 0, ',', '.') }}
                                </td>

                                {{-- Profit --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->profit, 0, ',', '.') }}
                                </td>

                                {{-- Tombol Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button" onclick="openModal('editModal-{{ $cementDeliveryOrder->no }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Data">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            {{-- Pesan jika data kosong --}}
                            <tr>
                                <td colspan="12" class="text-center p-4 text-text-secondary">
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
