{{-- =====================================================================
     Partial: Laporan Keuangan Proyek (dalam Laporan Akhir)
     Menampilkan rekap uang masuk, uang keluar, dan saldo per proyek
     berdasarkan transaksi "Bon" pada Laporan Keuangan Proyek masing-masing
     rekap. Dilengkapi ringkasan, grafik status, dan tombol "Detail" untuk
     melihat rincian transaksi per proyek (modal).
     Form disubmit ke route report.final dengan tab=financial agar tetap
     berada di halaman Laporan Akhir.
     Data dari FinalReportService::buildFinancialData:
     - $recaps : paginator Rekap Proyek (relasi financialReport.items)
     - $summary: ringkasan seluruh data yang lolos filter
     ===================================================================== --}}
<div class="space-y-6">

    {{-- ==================== FILTER SECTION ==================== --}}
    <div class="bg-surface-base p-6 rounded-xl shadow">
        <form method="GET" action="{{ route('report.final') }}" id="financialFilterForm"
            class="flex flex-col min-[1520px]:flex-row items-stretch min-[1520px]:items-center gap-3">

            {{-- Hidden: pertahankan tab aktif --}}
            <input type="hidden" name="tab" value="financial">

            {{-- Filter Bulan --}}
            <x-filters.month-filter :value="request('month')" responsive="custom" />

            {{-- Filter Tahun --}}
            <x-filters.year-filter :value="request('year')" responsive="custom" />

            {{-- Filter Status Laporan --}}
            <x-filters.select-filter name="status" :value="request('status')"
                :options="collect([
                    (object) ['id' => 'with_transactions', 'name' => 'Sudah Ada Transaksi'],
                    (object) ['id' => 'without_transactions', 'name' => 'Belum Ada Transaksi'],
                ])"
                placeholder="Semua Status" :autoSubmit="true" responsive="custom" />

            {{-- Search (submit manual) --}}
            <x-filters.search-input :value="request('search')" placeholder="Cari nama proyek atau lokasi..." responsive="custom" />
        </form>
    </div>

    {{-- ==================== SUMMARY CARDS ==================== --}}
    <div class="grid grid-cols-1 min-[1272px]:grid-cols-2 min-[1520px]:grid-cols-5 gap-4">

        {{-- Total Proyek --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total Proyek</p>
            <h3 class="text-2xl font-bold text-primary mt-1">{{ number_format($summary['total_projects']) }}</h3>
            <p class="text-xs text-text-secondary mt-1">Proyek yang tercatat</p>
        </div>

        {{-- Total Uang Masuk --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total Uang Masuk</p>
            <h3 class="text-xl font-bold text-success mt-1 truncate">Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</h3>
            <p class="text-xs text-text-secondary mt-1">Total pendapatan proyek</p>
        </div>

        {{-- Total Uang Keluar --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total Uang Keluar</p>
            <h3 class="text-xl font-bold text-error mt-1 truncate">Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}</h3>
            <p class="text-xs text-text-secondary mt-1">Total pengeluaran proyek</p>
        </div>

        {{-- Total Saldo --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total Saldo</p>
            <h3 class="text-xl font-bold {{ $summary['total_balance'] >= 0 ? 'text-primary' : 'text-error' }} mt-1 truncate">
                Rp {{ number_format(abs($summary['total_balance']), 0, ',', '.') }}
            </h3>
            <p class="text-xs text-text-secondary mt-1">
                {{ $summary['total_balance'] >= 0 ? 'Saldo surplus' : 'Saldo minus (defisit)' }}
            </p>
        </div>

        {{-- Total Transaksi --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total Transaksi</p>
            <h3 class="text-2xl font-bold text-text-primary mt-1">{{ number_format($summary['total_transactions']) }}</h3>
            <p class="text-xs text-text-secondary mt-1">Seluruh baris "Bon" tercatat</p>
        </div>
    </div>

    {{-- ==================== DETAIL TABEL ==================== --}}
    <div class="bg-surface-base rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b border-border-light">
            <h3 class="text-lg font-semibold text-text-primary">Detail Keuangan per Proyek</h3>
            <p class="text-sm text-text-secondary mt-1">Ringkasan uang masuk, uang keluar, dan saldo per proyek. Klik <strong>"Detail"</strong> untuk melihat rincian transaksi.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-secondary border-b border-border-light">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-12">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Nama Proyek</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Lokasi</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary">Dibuat</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Uang Masuk</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Uang Keluar</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Saldo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-28">Transaksi</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-32">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-28">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse($recaps as $recap)
                        @php
                            $finIncome = (int) $recap->fin_income;
                            $finExpense = (int) $recap->fin_expense;
                            $finBalance = (int) $recap->fin_balance;
                            $finTransactions = (int) $recap->fin_transactions;

                            if ($finTransactions > 0) {
                                $finStatusLabel = 'Ada Transaksi';
                                $finStatusClass = 'bg-green-100 text-green-800';
                            } else {
                                $finStatusLabel = 'Belum Ada';
                                $finStatusClass = 'bg-red-100 text-red-800';
                            }
                        @endphp
                        <tr class="hover:bg-surface-secondary transition-colors duration-150">
                            <td class="px-4 py-3 text-center text-sm font-medium text-primary">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-text-primary">{{ $recap->project_name }}</div>
                                @if ($recap->rab_number)
                                    <div class="text-xs text-text-secondary">No RAB: {{ $recap->rab_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-text-secondary">{{ $recap->location ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                                {{ $recap->created_at ? \Carbon\Carbon::parse($recap->created_at)->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-success">
                                Rp {{ number_format($finIncome, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-error">
                                Rp {{ number_format($finExpense, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-bold {{ $finBalance >= 0 ? 'text-primary' : 'text-error' }}">
                                Rp {{ number_format($finBalance, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 text-sm font-medium text-text-primary">
                                    <i class="fa-solid fa-receipt text-xs text-text-tertiary"></i>
                                    {{ number_format($finTransactions) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium {{ $finStatusClass }}">
                                    {{ $finStatusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($finTransactions > 0)
                                    <button type="button" onclick="openModal('finDetailModal-{{ $recap->id }}')"
                                        class="inline-flex items-center gap-1 bg-btn-search hover:bg-btn-search-hover text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200"
                                        title="Lihat rincian transaksi proyek ini">
                                        <i class="fa-solid fa-eye w-3 h-3"></i>
                                        Detail
                                    </button>
                                @else
                                    <span class="text-text-tertiary text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <p class="text-text-secondary">
                                    <i class="fas fa-inbox mr-2"></i>Tidak ada data laporan keuangan proyek untuk filter ini
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-border-light">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-sm text-text-primary font-semibold">
                    Halaman {{ $recaps->currentPage() }} dari {{ $recaps->lastPage() }}
                </div>
                <div>
                    {{ $recaps->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ==================== MODAL DETAIL TRANSAKSI PER PROYEK ==================== --}}
@foreach ($recaps as $recap)
    @php
        $finItems = optional($recap->financialReport)->items ?? collect();
        $finItems = $finItems->sortBy('transaction_date')->values();
        $finTotalIncome = (int) $finItems->where('is_informational', false)->sum('income_amount');
        $finTotalExpense = (int) $finItems->where('is_informational', false)->sum('expense_amount');
    @endphp

    <x-modal id="finDetailModal-{{ $recap->id }}"
        title="Rincian Keuangan — {{ $recap->project_name }}"
        :hideFooter="true" size="6xl">

        <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
            <div>
                <p class="text-sm text-text-secondary">
                    {{ $recap->id }}
                    @if ($recap->location)
                        <span class="text-text-label">({{ $recap->location }})</span>
                    @endif
                </p>
                <p class="text-sm text-text-secondary mt-1">Rincian transaksi "Bon" pada proyek ini.</p>
            </div>
            @if ($recap->financialReport)
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-primary-light text-primary gap-1">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    {{ $recap->financialReport->id }}
                </span>
            @endif
        </div>

        {{-- Ringkasan Finansial --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-xl border border-border-strong bg-surface-secondary p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Total Uang Masuk</p>
                <p class="text-xl font-bold text-success">Rp {{ number_format($finTotalIncome, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong bg-surface-secondary p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Total Uang Keluar</p>
                <p class="text-xl font-bold text-error">Rp {{ number_format($finTotalExpense, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong bg-surface-secondary p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Saldo</p>
                <p class="text-xl font-bold {{ ($finTotalIncome - $finTotalExpense) >= 0 ? 'text-primary' : 'text-error' }}">
                    Rp {{ number_format($finTotalIncome - $finTotalExpense, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Daftar Transaksi --}}
        <div class="mt-6">
            <h6 class="text-text-primary font-semibold mb-3">Daftar Transaksi ({{ $finItems->count() }})</h6>

            @if ($finItems->isEmpty())
                <div class="text-center p-4 border border-dashed border-border-strong rounded bg-surface-secondary">
                    <p class="text-sm text-text-secondary">Belum ada transaksi pada proyek ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-surface-secondary">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-10">No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Keterangan</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Jumlah</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Keterangan Bon</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($finItems as $index => $item)
                                @php
                                    $isIncome = (int) ($item->income_amount ?? 0) > 0;
                                    $amount = (int) ($item->income_amount ?: $item->expense_amount);
                                @endphp
                                <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                    <td class="px-4 py-3 text-center text-sm font-medium text-primary">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                                        {{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary">
                                        {{ $item->category->name ?? '-' }}
                                        @if ($item->is_informational)
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-blue-100 text-blue-700"
                                                title="Baris informasi — tidak memengaruhi total laporan">
                                                <i class="fa-solid fa-circle-info w-2.5 h-2.5 mr-0.5"></i> Info
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary">{{ $item->description }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-medium {{ $isIncome ? 'text-success' : 'text-error' }}">
                                        @if ($item->is_informational)
                                            <span class="text-text-tertiary">-</span>
                                        @else
                                            {{ $isIncome ? '+' : '-' }} Rp {{ number_format($amount, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-secondary">{{ $item->keterangan_bon ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        @if ($item->hasProof())
                                            <a href="{{ asset('storage/' . $item->proof_file) }}" target="_blank"
                                                rel="noopener noreferrer" title="{{ $item->proof_file_name }}"
                                                class="text-blue-600 hover:underline">
                                                <i class="fa-solid fa-paperclip"></i> File
                                            </a>
                                        @else
                                            <span class="text-text-tertiary">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </x-modal>
@endforeach
