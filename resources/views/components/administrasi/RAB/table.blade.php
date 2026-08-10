{{--
    Component Tabel RAB
    Menampilkan daftar RAB dalam tabel dengan checkbox, informasi header, dan tombol aksi.

    Data:
    - $rabs: Collection dari RAB dengan pagination
--}}
<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <form method="POST" action="{{ route('rab.destroy') }}" id="deleteForm">
        @csrf
        @method('DELETE')
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-3 text-center"><input type="checkbox" id="selectAll"
                                    class="w-4 h-4 accent-primary cursor-pointer"></th>
                            <th class="p-3 text-left">No. RAB</th>
                            <th class="p-3 text-left">Proyek</th>
                            <th class="p-3 text-left">Penerima</th>
                            <th class="p-3 text-left">Tanggal</th>
                            <th class="p-3 text-right">Total Biaya</th>
                            @if (auth()->user()->isSuperAdmin())
                                <th class="p-3 text-right">Uang Masuk (DP)</th>
                                <th class="p-3 text-right">Sisa</th>
                            @endif
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
                                <td class="p-3 font-medium text-text-primary">{{ $rab->project_name ?? '-' }}</td>
                                <td class="p-3">{{ $rab->recipient }}</td>
                                <td class="p-3 text-sm text-text-secondary">
                                    {{ \Carbon\Carbon::parse($rab->date)->isoFormat('DD MMM YYYY') }}
                                </td>
                                <td class="p-3 text-right font-semibold text-success">
                                    Rp {{ number_format($rab->total_amount, 0, ',', '.') }}
                                </td>
                                @if (auth()->user()->isSuperAdmin())
                                    <td class="p-3 text-right font-semibold text-primary">
                                        Rp {{ number_format($rab->incoming_payment ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-right font-semibold text-error">
                                        Rp {{ number_format(($rab->total_amount ?? 0) - ($rab->incoming_payment ?? 0), 0, ',', '.') }}
                                    </td>
                                @endif
                                <td class="p-3 text-center">
                                    <div class="flex justify-center gap-[5px] flex-wrap min-[1363px]:grid min-[1363px]:gap-[5px] min-[1363px]:grid-cols-[auto_auto] min-[1363px]:w-fit min-[1363px]:mx-auto min-[1436px]:grid-cols-[auto_auto_auto_auto] min-[1436px]:gap-[5px]">
                                        {{-- Tombol Detail --}}
                                        <button type="button"
                                            onclick="openModal('detailRABModal{{ $rab->rab_number }}')"
                                            class="flex items-center gap-1 bg-primary hover:bg-primary-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Detail">
                                            <i class="fa-solid fa-eye w-3 h-3"></i>
                                            Detail
                                        </button>

                                        {{-- Tombol Edit --}}
                                        <button type="button" onclick="editRAB('{{ $rab->rab_number }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>

                                        {{-- Tombol Export PDF --}}
                                        <a href="{{ route('rab.export-pdf', $rab->rab_number) }}"
                                            class="flex items-center gap-1 bg-error hover:bg-error/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Download PDF">
                                            <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                            PDF
                                        </a>

                                        {{-- Tombol Export Excel --}}
                                        <a href="{{ route('rab.export-excel', $rab->rab_number) }}"
                                            class="flex items-center gap-1 bg-success hover:bg-success-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Download Excel">
                                            <i class="fa-solid fa-file-excel w-3 h-3"></i>
                                            Excel
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isSuperAdmin() ? 9 : 7 }}" class="p-4 text-center text-text-secondary">Tidak ada RAB.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
