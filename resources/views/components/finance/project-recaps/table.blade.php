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
                            @if (auth()->user()->role === 'superadmin')
                                <th class="p-2 text-right">Uang Masuk (DP)</th>
                            @endif
                            <th class="p-2 text-right">Terbayar</th>
                            <th class="p-2 text-right">Sisa Pembayaran</th>
                            <th class="p-2 text-center">Progress</th>
                            <th class="p-2 text-center">File Design</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-base divide-y divide-border-light">
                        @forelse ($recaps as $recap)
                            @php
                                $recapTotal = $recap->getTotalAmount();
                                $recapDp = $recap->getDpAmount();
                                $recapPaid = $recap->getTotalPaidAmount();
                                $recapRemaining = $recap->getRemainingAmount();
                                $recapProgress = $recap->getProgressPercent();

                                if ($recap->isFullyPaid()) {
                                    $recapStatusLabel = 'Sudah Lunas';
                                    $recapStatusClass = 'bg-green-100 text-green-800';
                                    $recapProgressColor = 'bg-green-500';
                                } elseif ($recapPaid > 0) {
                                    $recapStatusLabel = 'Sebagian';
                                    $recapStatusClass = 'bg-orange-100 text-orange-800';
                                    $recapProgressColor = 'bg-orange-400';
                                } else {
                                    $recapStatusLabel = 'Belum Ada';
                                    $recapStatusClass = 'bg-red-100 text-red-800';
                                    $recapProgressColor = 'bg-red-400';
                                }
                            @endphp
                            <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_recaps[]" value="{{ $recap->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>
                                <td class="p-2 text-center font-medium text-primary">{{ $recap->id }}</td>
                                <td class="p-2 font-medium text-text-primary">{{ $recap->project_name }}</td>
                                <td class="p-2 text-right font-medium text-text-primary">Rp
                                    {{ number_format($recapTotal, 0, ',', '.') }}</td>
                                @if (auth()->user()->role === 'superadmin')
                                    <td class="p-2 text-right font-semibold text-blue-600">
                                        Rp {{ number_format($recapDp, 0, ',', '.') }}
                                    </td>
                                @endif
                                <td class="p-2 text-right font-medium text-green-600">
                                    Rp {{ number_format($recapPaid, 0, ',', '.') }}
                                </td>
                                <td class="p-2 text-right font-semibold {{ $recapRemaining > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    Rp {{ number_format($recapRemaining, 0, ',', '.') }}
                                </td>
                                <td class="p-2 text-center">
                                    <div class="flex items-center gap-1.5 w-full max-w-[90px] mx-auto">
                                        <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $recapProgressColor }}"
                                                style="width: {{ $recapProgress }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-medium text-gray-400 tabular-nums">{{ $recapProgress }}%</span>
                                    </div>
                                </td>
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
                                    <div class="flex justify-center gap-[5px] flex-wrap min-[1363px]:grid min-[1363px]:gap-[5px] min-[1363px]:grid-cols-[auto_auto] min-[1363px]:w-fit min-[1363px]:mx-auto">
                                        <button type="button"
                                            onclick="openModal('detailModal-{{ $recap->id }}')"
                                            class="flex items-center gap-1 bg-info hover:bg-info/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Lihat Detail">
                                            <i class="fa-solid fa-eye w-3 h-3"></i>
                                            Lihat
                                        </button>

                                        <button type="button"
                                            onclick="openModal('editModal-{{ $recap->id }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Rekap Proyek">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role === 'superadmin' ? 10 : 9 }}" class="text-center p-4 text-text-secondary">Data rekap proyek tidak
                                    ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
