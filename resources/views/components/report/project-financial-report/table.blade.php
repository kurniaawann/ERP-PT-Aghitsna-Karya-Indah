@props(['recaps'])

{{-- ==================== Tabel Daftar Laporan Keuangan Proyek ==================== --}}
<form id="deleteForm" method="POST" action="{{ route('recap-proyek.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll" class="w-4 h-4 accent-primary cursor-pointer"></th>
                            <th class="p-2 text-center">No</th>
                        <th class="p-2 text-left">Tanggal</th>
                        <th class="p-2 text-left">Nama Proyek</th>
                        <th class="p-2 text-left">Lokasi</th>
                        <th class="p-2 text-right">Saldo</th>
                        <th class="p-2 text-center">Status</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-surface-base divide-y divide-border-light">
                    @forelse ($recaps as $recap)
                        @php
                            $report = $recap->financialReport;
                            $totalIncome = (int) ($report ? $report->items->sum('income_amount') : 0);
                            $totalExpense = (int) ($report ? $report->items->sum('expense_amount') : 0);
                            $balance = $totalIncome - $totalExpense;

                            $recapPaid = $recap->getTotalPaidAmount();
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
                            <td class="p-2 text-center text-text-secondary">
                                {{ $recap->created_at ? \Carbon\Carbon::parse($recap->created_at)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="p-2 font-medium text-text-primary">{{ $recap->project_name }}</td>
                            <td class="p-2 text-text-secondary">{{ $recap->location ?: '-' }}</td>
                            <td class="p-2 text-right font-semibold {{ $balance >= 0 ? 'text-success' : 'text-error' }}">
                                Rp {{ number_format($balance, 0, ',', '.') }}
                            </td>
                            <td class="p-2 text-center">
                                <div class="flex flex-col items-center gap-1.5 w-full max-w-[140px] mx-auto">
                                    <div class="flex-1 w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $recapProgressColor }}"
                                            style="width: {{ $recapProgress }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-medium text-gray-400 tabular-nums">
                                        {{ $recapProgress }}% &middot; <span class="{{ $recapStatusClass }} px-1.5 py-0.5 rounded-full">{{ $recapStatusLabel }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="p-2 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <button type="button" onclick="openModal('detailModal-{{ $recap->id }}')"
                                        class="flex items-center gap-1 bg-btn-search hover:bg-btn-search-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Lihat Detail Laporan Keuangan Proyek">
                                        <i class="fa-solid fa-eye w-3 h-3"></i>
                                        Detail
                                    </button>
                                    <button type="button" onclick="openModal('editPfrModal-{{ $recap->id }}')"
                                        class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Edit Rekap Proyek & Transaksi">
                                        <i class="fa-solid fa-pen w-3 h-3"></i>
                                        Edit
                                    </button>
                                    <a href="{{ route('project-financial-report.export.pdf', $recap) }}"
                                        class="flex items-center gap-1 bg-error hover:bg-error text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Export PDF">
                                        <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                        PDF
                                    </a>
                                    <a href="{{ route('project-financial-report.export.excel', $recap) }}"
                                        class="flex items-center gap-1 bg-success hover:bg-success text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Export Excel">
                                        <i class="fa-solid fa-file-excel w-3 h-3"></i>
                                        Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-4 text-text-secondary">
                                Data laporan keuangan proyek tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</form>
