{{-- =====================================================================
     Executive Table Component
     Menampilkan daftar petinggi dalam format tabel dengan checkbox
     untuk bulk delete, pratinjau tanda tangan, dan tombol edit per baris.
     ===================================================================== --}}
<form id="deleteForm" method="POST" action="{{ route('executive.destroy') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    {{-- Header Tabel --}}
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">Nama</th>
                            <th class="p-2 text-left">Jabatan</th>
                            <th class="p-2 text-center">Tanda Tangan</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($executives as $executive)
                            <tr class="border-t hover:bg-surface-secondary">
                                {{-- Checkbox untuk bulk delete --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $executive->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                {{-- Nama Petinggi --}}
                                <td class="p-2 font-medium text-primary">{{ $executive->name }}</td>

                                {{-- Jabatan --}}
                                <td class="p-2">{{ $executive->position ?? '-' }}</td>

                                {{-- Tanda Tangan (pratinjau gambar) --}}
                                <td class="p-2 text-center">
                                    @if ($executive->signature_image)
                                        <button type="button"
                                            onclick="openSignature('{{ asset('storage/' . $executive->signature_image) }}')"
                                            title="Klik untuk perbesar" class="inline-block">
                                            <img src="{{ asset('storage/' . $executive->signature_image) }}"
                                                alt="Tanda tangan {{ $executive->name }}"
                                                class="h-12 w-auto max-w-[6rem] object-contain border border-border rounded bg-white cursor-pointer hover:opacity-80 transition-opacity">
                                        </button>
                                    @else
                                        <span class="text-text-tertiary">-</span>
                                    @endif
                                </td>

                                {{-- Tombol Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button" onclick="openModal('editModal-{{ $executive->id }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Petinggi">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center p-4 text-text-secondary">
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
