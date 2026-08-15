{{-- Table DO Semen (master-detail: header DO + baris Data Semen) --}}
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
                            {{-- Baris Header DO --}}
                            <tr class="border-t bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_items[]"
                                        value="{{ $cementDeliveryOrder->no }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>
                                <td class="p-2 font-semibold text-primary">{{ $cementDeliveryOrder->no }}</td>
                                <td class="p-2">
                                    {{ $cementDeliveryOrder->tanggal?->format('d M Y') ?: '-' }}
                                    <div class="text-xs text-text-secondary">
                                        Datang: {{ $cementDeliveryOrder->tanggal_datang?->format('d M Y') ?: '-' }}
                                        · Bayar: {{ $cementDeliveryOrder->tanggal_bayar?->format('d M Y') ?: '-' }}
                                    </div>
                                </td>
                                <td class="p-2 text-xs text-text-secondary">
                                    {{ $cementDeliveryOrder->jumlah_baris }} baris ·
                                    {{ number_format($cementDeliveryOrder->total_volume, 0, ',', '.') }} zak
                                </td>
                                <td class="p-2 text-center text-text-secondary">-</td>
                                <td class="p-2 text-center text-text-secondary">-</td>
                                <td class="p-2 text-right text-text-secondary">-</td>
                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->subtotal, 0, ',', '.') }}
                                </td>
                                <td class="p-2 text-text-secondary">-</td>
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->harga_modal, 0, ',', '.') }}
                                </td>
                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($cementDeliveryOrder->profit, 0, ',', '.') }}
                                </td>
                                <td class="p-2 text-center">
                                    <button type="button"
                                        onclick="openModal('editModal-{{ $cementDeliveryOrder->no }}')"
                                        class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Edit Data">
                                        <i class="fa-solid fa-pen w-3 h-3"></i>
                                        Edit
                                    </button>
                                </td>
                            </tr>

                            {{-- Baris Detail Data Semen --}}
                            @forelse ($cementDeliveryOrder->cements as $cement)
                                <tr class="border-t bg-white">
                                    <td class="p-2"></td>
                                    <td class="p-2 pl-6 text-xs text-text-secondary">{{ $cement->no }}</td>
                                    <td class="p-2">{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>
                                    <td class="p-2">{{ $cement->nama_proyek }}
                                        @if ($cement->name)
                                            <div class="text-xs text-text-secondary">{{ $cement->name }}</div>
                                        @endif
                                    </td>
                                    <td class="p-2 text-center">{{ number_format($cement->jumlah, 0, ',', '.') }}</td>
                                    <td class="p-2 text-center">{{ $cement->satuan ?: 'zak' }}</td>
                                    <td class="p-2 text-right">{{ 'Rp ' . number_format($cement->harga, 0, ',', '.') }}</td>
                                    <td class="p-2 text-right font-medium">
                                        {{ 'Rp ' . number_format($cement->total, 0, ',', '.') }}
                                    </td>
                                    <td class="p-2">{{ $cement->tanggal_lunas?->format('d M Y') ?: '-' }}</td>
                                    <td class="p-2 text-center text-text-secondary">-</td>
                                    <td class="p-2 text-right">{{ 'Rp ' . number_format($cement->profit, 0, ',', '.') }}</td>
                                    <td class="p-2"></td>
                                </tr>
                            @empty
                                <tr class="border-t bg-white">
                                    <td colspan="11" class="p-2 pl-6 text-xs text-text-secondary italic">
                                        Tidak ada data semen dalam DO ini.
                                    </td>
                                </tr>
                            @endforelse
                        @empty
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
