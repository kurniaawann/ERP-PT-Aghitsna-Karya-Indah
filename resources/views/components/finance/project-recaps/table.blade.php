@props(['recaps'])

{{-- ==================== Tabel Rekap Proyek ==================== --}}
<form id="deleteForm" method="POST" action="{{ route('recap-proyek.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-center">No</th>
                            <th class="p-2 text-left">Nama Proyek</th>
                            <th class="p-2 text-right">Total RAB</th>
                            <th class="p-2 text-center">File Design</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base divide-y divide-border-light">
                        @forelse ($recaps as $recap)
                            <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_recaps[]" value="{{ $recap->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>
                                <td class="p-2 text-center font-medium text-primary">{{ $recap->id }}</td>
                                <td class="p-2 font-medium text-text-primary">{{ $recap->project_name }}</td>
                                <td class="p-2 text-right font-medium text-text-primary">Rp
                                    {{ number_format($recap->total_rab ?? 0, 0, ',', '.') }}</td>
                                <td class="p-2 text-sm text-center">
                                    @if ($recap->hasDesignFile())
                                        <a href="{{ asset('storage/' . $recap->design_file) }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-primary hover:underline"
                                            title="Lihat/unduh {{ $recap->design_file_name }}">
                                            <i class="fa-solid fa-image text-xs"></i>
                                            {{ \Illuminate\Support\Str::limit($recap->design_file_name, 30) }}
                                        </a>
                                    @else
                                        <span class="text-text-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="p-2 text-center">
                                    <button type="button"
                                        onclick="openModal('editModal-{{ $recap->id }}')"
                                        class="flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 mx-auto">
                                        <i class="fa-solid fa-pen w-4 h-4"></i>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4 text-text-secondary">Data rekap proyek tidak
                                    ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
