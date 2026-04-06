<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <form method="POST" action="{{ route('rab.destroy') }}" id="deleteForm">
        @csrf
        @method('DELETE')
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-3 text-center"><input type="checkbox" id="selectAll"
                                    class="w-4 h-4 accent-primary cursor-pointer"></th>
                            <th class="p-3 text-left">No. RAB</th>
                            <th class="p-3 text-left">Penerima</th>
                            <th class="p-3 text-left">Tanggal</th>
                            <th class="p-3 text-right">Total Biaya</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rabs as $rab)
                            <tr class="border-t hover:bg-surface-secondary transition-colors duration-200">
                                <td class="p-3 text-center">
                                    <input type="checkbox" name="selected_items[]" value="{{ $rab->rab_number }}"
                                        class="w-4 h-4 accent-primary cursor-pointer item-checkbox">
                                </td>
                                <td class="p-3 font-medium text-primary">{{ $rab->rab_number }}</td>
                                <td class="p-3">{{ $rab->recipient }}</td>
                                <td class="p-3 text-sm text-gray-600">
                                    {{ \Carbon\Carbon::parse($rab->date)->isoFormat('DD MMM YYYY') }}
                                </td>
                                <td class="p-3 text-right font-semibold text-green-600">
                                    Rp {{ number_format($rab->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex justify-center gap-1 flex-wrap">
                                        {{-- View Detail Modal --}}
                                        <button type="button"
                                            onclick="openModal('detailRABModal{{ $rab->rab_number }}')"
                                            class="flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs font-medium"
                                            title="Lihat Detail">
                                            <i class="fa-solid fa-eye w-3 h-3"></i>
                                            Lihat
                                        </button>
                                        {{-- Download PDF --}}
                                        <a href="{{ route('rab.export-pdf', $rab->rab_number) }}"
                                            class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs font-medium"
                                            title="Download PDF">
                                            <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                            PDF
                                        </a>
                                        {{-- Edit --}}
                                        <button type="button" onclick="editRAB('{{ $rab->rab_number }}')"
                                            class="flex items-center gap-1 bg-primary hover:bg-primary-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs font-medium"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-500">Tidak ada RAB.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
