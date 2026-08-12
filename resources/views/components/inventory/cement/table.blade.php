{{-- Table Data Semen --}}
<form id="deleteForm" method="POST" action="{{ route('cements.destroySelected') }}">
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
                            <th class="p-2 text-left">Nama Proyek</th>
                            <th class="p-2 text-center">Jumlah</th>
                            <th class="p-2 text-right">Harga</th>
                            <th class="p-2 text-right">Total</th>
                            <th class="p-2 text-left">Tanggal Lunas</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>

                    {{-- Body Tabel --}}
                    <tbody>
                        @forelse($cements as $cement)
                            <tr class="border-t hover:bg-surface-secondary">

                                {{-- Checkbox --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_items[]" value="{{ $cement->no }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                {{-- No --}}
                                <td class="p-2 font-medium text-primary">{{ $cement->no }}</td>

                                {{-- Tanggal --}}
                                <td class="p-2">{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>

                                {{-- Nama Proyek --}}
                                <td class="p-2">{{ $cement->nama_proyek }}</td>

                                {{-- Jumlah Sak --}}
                                <td class="p-2 text-center">{{ number_format($cement->jumlah, 0, ',', '.') }}</td>

                                {{-- Harga Per Sak --}}
                                <td class="p-2 text-right">
                                    {{ 'Rp ' . number_format($cement->harga, 0, ',', '.') }}
                                </td>

                                {{-- Total (Harga Per Sak x Jumlah Sak) --}}
                                <td class="p-2 text-right font-medium">
                                    {{ 'Rp ' . number_format($cement->total, 0, ',', '.') }}
                                </td>

                                {{-- Tanggal Lunas --}}
                                <td class="p-2">{{ $cement->tanggal_lunas?->format('d M Y') ?: '-' }}</td>

                                {{-- Tombol Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button" onclick="openModal('editModal-{{ $cement->no }}')"
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
                                <td colspan="9" class="text-center p-4 text-text-secondary">
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
