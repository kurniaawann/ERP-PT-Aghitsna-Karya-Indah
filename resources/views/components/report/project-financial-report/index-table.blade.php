@props(['recaps'])

{{-- ==================== Tabel Daftar Laporan Keuangan Proyek ==================== --}}
<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-border-light">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="p-2 text-center">No</th>
                        <th class="p-2 text-left">Nama Proyek</th>
                        <th class="p-2 text-left">Lokasi</th>
                        <th class="p-2 text-right">Total RAB</th>
                        <th class="p-2 text-center">Laporan</th>
                        <th class="p-2 text-right">Uang Masuk</th>
                        <th class="p-2 text-right">Uang Keluar</th>
                        <th class="p-2 text-right">Saldo</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-surface-base divide-y divide-border-light">
                    @forelse ($recaps as $recap)
                        @php
                            $report = $recap->financialReport;
                            $totalIncome = (int) optional($report)->items->sum('income_amount');
                            $totalExpense = (int) optional($report)->items->sum('expense_amount');
                            $balance = $totalIncome - $totalExpense;
                        @endphp
                        <tr class="hover:bg-surface-secondary transition-colors duration-150">
                            <td class="p-2 text-center font-medium text-primary">{{ $recap->id }}</td>
                            <td class="p-2 font-medium text-text-primary">{{ $recap->project_name }}</td>
                            <td class="p-2 text-text-secondary">{{ $recap->location ?: '-' }}</td>
                            <td class="p-2 text-right font-medium text-text-primary">
                                Rp {{ number_format((int) $recap->total_rab, 0, ',', '.') }}
                            </td>
                            <td class="p-2 text-center">
                                @if ($report)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-primary-light text-primary">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                        {{ $report->id }}
                                    </span>
                                @else
                                    <span class="text-text-tertiary">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="p-2 text-right font-medium text-green-600">
                                Rp {{ number_format($totalIncome, 0, ',', '.') }}
                            </td>
                            <td class="p-2 text-right font-medium text-error">
                                Rp {{ number_format($totalExpense, 0, ',', '.') }}
                            </td>
                            <td class="p-2 text-right font-semibold {{ $balance >= 0 ? 'text-text-primary' : 'text-error' }}">
                                Rp {{ number_format($balance, 0, ',', '.') }}
                            </td>
                            <td class="p-2 text-center">
                                <a href="{{ route('project-financial-report.show', $recap) }}"
                                    class="flex items-center gap-1 bg-btn-search hover:bg-btn-search-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs w-fit mx-auto"
                                    title="Buka Laporan Keuangan Proyek">
                                    <i class="fa-solid fa-file-invoice-dollar w-3 h-3"></i>
                                    Buka Laporan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center p-4 text-text-secondary">
                                Data laporan keuangan proyek tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
