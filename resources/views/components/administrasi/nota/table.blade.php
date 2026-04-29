{{-- Nota Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('nota.administrasi.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No. Nota</th>
                            <th class="p-2 text-left">Kepada</th>
                            <th class="p-2 text-left">Faktur No</th>
                            <th class="p-2 text-left">SJ No</th>
                            <th class="p-2 text-right">Jumlah Total</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notas as $nota)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $nota->id_nota }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $nota->id_nota }}</td>
                                <td class="p-2">{{ $nota->kepada }}</td>
                                <td class="p-2">{{ $nota->faktur_no }}</td>
                                <td class="p-2">{{ $nota->sj_no }}</td>

                                {{-- Jumlah Total --}}
                                <td class="p-2 text-right font-semibold text-green-600">
                                    Rp {{ number_format($nota->jumlah_total, 0, ',', '.') }}
                                </td>

                                {{-- Tanggal --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($nota->nota_date)->format('d/m/Y') }}
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- Button Edit --}}
                                        <button type="button" onclick="openModal('editModal-{{ $nota->id_nota }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Nota">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center p-4 text-text-secondary">
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
