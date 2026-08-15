{{-- =====================================================================
     Partial: Rekap Proyek (dalam Laporan Akhir)
     Menampilkan rekap proyek: nilai proyek (Total RAB), pembayaran yang
     sudah masuk, dan sisa tagihan — lengkap dengan progress & status.
     Form disubmit ke route report.final dengan tab=recap agar tetap berada
     di halaman Laporan Akhir. Export per proyek tersedia pada halaman
     Laporan Keuangan Proyek.
     Data dari FinalReportService::buildRecapData:
     - $recaps : paginator Rekap Proyek (dengan relasi rab, paymentProofs,
                financialReport.items)
     - $summary: ringkasan seluruh data yang lolos filter
     ===================================================================== --}}
<div class="space-y-6">

    {{-- ==================== FILTER SECTION ==================== --}}
    <div class="bg-surface-base p-6 rounded-xl shadow">
        <form method="GET" action="{{ route('report.final') }}" id="recapFilterForm"
            class="flex flex-col min-[1520px]:flex-row items-stretch min-[1520px]:items-center gap-3">

            {{-- Hidden: pertahankan tab aktif --}}
            <input type="hidden" name="tab" value="recap">

            {{-- Filter Bulan --}}
            <x-filters.month-filter :value="request('month')" responsive="custom" />

            {{-- Filter Tahun --}}
            <x-filters.year-filter :value="request('year')" responsive="custom" />

            {{-- Filter Status Pembayaran --}}
            <x-filters.select-filter name="status" :value="request('status')"
                :options="collect([
                    (object) ['id' => 'lunas', 'name' => 'Sudah Lunas'],
                    (object) ['id' => 'sebagian', 'name' => 'Sebagian'],
                    (object) ['id' => 'belum', 'name' => 'Belum Ada Pembayaran'],
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
            <p class="text-xs text-text-secondary mt-1">Rekap proyek yang dibuat</p>
        </div>

        {{-- Total RAB --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total RAB</p>
            <h3 class="text-xl font-bold text-text-primary mt-1 truncate">Rp {{ number_format($summary['total_rab'], 0, ',', '.') }}</h3>
            <p class="text-xs text-text-secondary mt-1">Nilai keseluruhan proyek</p>
        </div>

        {{-- Total Terbayar --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total Terbayar</p>
            <h3 class="text-xl font-bold text-success mt-1 truncate">Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}</h3>
            <p class="text-xs text-text-secondary mt-1">Pembayaran yang sudah masuk</p>
        </div>

        {{-- Total Sisa --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Total Sisa Tagihan</p>
            <h3 class="text-xl font-bold text-warning mt-1 truncate">Rp {{ number_format($summary['total_remaining'], 0, ',', '.') }}</h3>
            <p class="text-xs text-text-secondary mt-1">Belum dibayar</p>
        </div>

        {{-- Proyek Lunas --}}
        <div class="bg-surface-base p-5 rounded-xl shadow hover:shadow-lg transition-shadow duration-200">
            <p class="text-sm font-medium text-text-secondary">Proyek Lunas</p>
            <h3 class="text-2xl font-bold text-success mt-1">{{ number_format($summary['paid_projects']) }}</h3>
            <p class="text-xs text-text-secondary mt-1">dari {{ number_format($summary['total_projects']) }} proyek</p>
        </div>
    </div>

    {{-- ==================== DETAIL TABEL ==================== --}}
    <div class="bg-surface-base rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b border-border-light">
            <h3 class="text-lg font-semibold text-text-primary">Detail Rekap Proyek</h3>
            <p class="text-sm text-text-secondary mt-1">Rekap nilai proyek, uang masuk, pembayaran, dan sisa tagihan per proyek.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-secondary border-b border-border-light">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-12">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Nama Proyek</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Lokasi</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary">Dibuat</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Total RAB</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Uang Masuk</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Terbayar</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Sisa Tagihan</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-40">Progress</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-32">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-28">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse($recaps as $recap)
                        @php
                            $recapPaid = $recap->getTotalPaidAmount();
                            $recapProgress = $recap->getProgressPercent();
                            $recapRemaining = $recap->getRemainingAmount();

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
                            <td class="px-4 py-3 text-right text-sm font-medium text-text-primary">
                                Rp {{ number_format($recap->getTotalAmount(), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-text-primary">
                                Rp {{ number_format($recap->getDpAmount(), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-success">
                                Rp {{ number_format($recapPaid, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium {{ $recapRemaining > 0 ? 'text-warning' : 'text-success' }}">
                                Rp {{ number_format($recapRemaining, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $recapProgressColor }}"
                                            style="width: {{ $recapProgress }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-text-secondary tabular-nums w-9 text-right">{{ $recapProgress }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium {{ $recapStatusClass }}">
                                    {{ $recapStatusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <button type="button" onclick="openModal('recapDetailModal-{{ $recap->id }}')"
                                        class="inline-flex items-center gap-1 bg-btn-search hover:bg-btn-search-hover text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200"
                                        title="Lihat riwayat pembayaran proyek ini">
                                        <i class="fa-solid fa-eye w-3 h-3"></i>
                                        Detail
                                    </button>
                                    <a href="{{ route('project-financial-report.export.pdf', $recap) }}"
                                        class="inline-flex items-center gap-1 bg-error hover:bg-error text-white px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200"
                                        title="Export Laporan Keuangan PDF">
                                        <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                        PDF
                                    </a>
                                    <a href="{{ route('project-financial-report.export.excel', $recap) }}"
                                        class="inline-flex items-center gap-1 bg-success hover:bg-success text-white px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200"
                                        title="Export Laporan Keuangan Excel">
                                        <i class="fa-solid fa-file-excel w-3 h-3"></i>
                                        Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center">
                                <p class="text-text-secondary">
                                    <i class="fas fa-inbox mr-2"></i>Tidak ada data rekap proyek untuk filter ini
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

{{-- ==================== MODAL DETAIL PEMBAYARAN PER PROYEK ==================== --}}
@foreach ($recaps as $recap)
    @php
        $recapProofs = $recap->paymentProofs;
        $recapTotalPaid = $recap->getTotalPaidAmount();
        $recapIncomePayments = $recap->getIncomePayments();
    @endphp

    <x-modal id="recapDetailModal-{{ $recap->id }}"
        title="Riwayat Pembayaran — {{ $recap->project_name }}"
        :hideFooter="true" size="6xl">

        <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
            <div>
                <p class="text-sm text-text-secondary">
                    {{ $recap->id }}
                    @if ($recap->location)
                        <span class="text-text-label">({{ $recap->location }})</span>
                    @endif
                </p>
                <p class="text-sm text-text-secondary mt-1">Daftar bukti pembayaran (uang masuk) pada proyek ini.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('project-financial-report.export.pdf', $recap) }}"
                    class="inline-flex items-center gap-1 bg-error hover:bg-error text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200">
                    <i class="fa-solid fa-file-pdf w-3 h-3"></i> PDF
                </a>
                <a href="{{ route('project-financial-report.export.excel', $recap) }}"
                    class="inline-flex items-center gap-1 bg-success hover:bg-success text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors duration-200">
                    <i class="fa-solid fa-file-excel w-3 h-3"></i> Excel
                </a>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-xl border border-border-strong bg-surface-secondary p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Total RAB</p>
                <p class="text-xl font-bold text-text-primary">Rp {{ number_format($recap->getTotalAmount(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong bg-surface-secondary p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Total Terbayar</p>
                <p class="text-xl font-bold text-success">Rp {{ number_format($recapTotalPaid, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong bg-surface-secondary p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Sisa Tagihan</p>
                <p class="text-xl font-bold {{ $recap->getRemainingAmount() > 0 ? 'text-warning' : 'text-success' }}">
                    Rp {{ number_format($recap->getRemainingAmount(), 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Daftar Bukti Pembayaran --}}
        <div class="mt-6">
            <h6 class="text-text-primary font-semibold mb-3">Daftar Pembayaran ({{ $recapProofs->count() }})</h6>

            @if ($recapProofs->isEmpty())
                <div class="text-center p-4 border border-dashed border-border-strong rounded bg-surface-secondary">
                    <p class="text-sm text-text-secondary">Belum ada bukti pembayaran pada proyek ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-surface-secondary">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-10">No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Tanggal</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Nominal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Tahap</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($recapProofs as $index => $proof)
                                <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                    <td class="px-4 py-3 text-center text-sm font-medium text-primary">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                                        {{ $proof->payment_date ? \Carbon\Carbon::parse($proof->payment_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-success">
                                        Rp {{ number_format((int) $proof->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary">
                                        {{ $proof->payment_stage ? 'Tahap ' . $proof->payment_stage : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        @if ($proof->file_path)
                                            <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank"
                                                rel="noopener noreferrer" title="{{ $proof->file_name }}"
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

        {{-- Daftar Uang Masuk (Laporan Keuangan) --}}
        @if ($recapIncomePayments->isNotEmpty())
            <div class="mt-6">
                <h6 class="text-text-primary font-semibold mb-3">Uang Masuk (Laporan Keuangan) ({{ $recapIncomePayments->count() }})</h6>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-surface-secondary">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary w-10">No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-text-secondary">Keterangan</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-text-secondary">Nominal</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-text-secondary">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($recapIncomePayments as $index => $income)
                                <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                    <td class="px-4 py-3 text-center text-sm font-medium text-primary">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">
                                        {{ $income->transaction_date ? \Carbon\Carbon::parse($income->transaction_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-primary">
                                        {{ $income->category?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-text-secondary">{{ $income->description ?: '-' }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-success">
                                        Rp {{ number_format((int) $income->income_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        @if ($income->proof_file)
                                            <a href="{{ asset('storage/' . $income->proof_file) }}" target="_blank"
                                                rel="noopener noreferrer" title="{{ $income->proof_file_name }}"
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
            </div>
        @endif

    </x-modal>
@endforeach
