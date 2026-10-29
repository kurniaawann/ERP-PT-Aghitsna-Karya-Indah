{{-- =====================================================================
     Partial: Rekap Proyek (dalam Laporan Akhir, gaya Filament)
     Menampilkan rekap proyek: nilai proyek (Total RAB), pembayaran yang
     sudah masuk, dan sisa tagihan — lengkap dengan progress & status.
     Form disubmit ke route report.final dengan tab=recap agar tetap berada
     di halaman Laporan Akhir. Export Laporan Keuangan per proyek (PDF/Excel)
     ada di menu Aksi ⋮ tiap baris & di modal Riwayat Pembayaran.
     Data dari FinalReportService::buildRecapData:
     - $recaps : paginator Rekap Proyek (dengan relasi rab, paymentProofs,
                financialReport.items). Terbayar = bukti rekap + pembayaran
                invoice tertaut (tanpa PPN) + uang masuk manual laporan
                (ProjectRecap::getTotalPaidAmount).
     - $summary: ringkasan seluruh data yang lolos filter
     ===================================================================== --}}
@php
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Filter (bulan, tahun, status pembayaran, pencarian) --}}
    <x-report.final-report.filter-bar tab="recap" formId="recapFilterForm">
        <div class="sm:w-44">
            <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="sm:w-36">
            <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="col-span-2 sm:w-52">
            <x-fi.select name="status" aria-label="Filter Status" placeholder="Semua Status"
                :options="['lunas' => 'Sudah Lunas', 'sebagian' => 'Sebagian', 'belum' => 'Belum Ada Pembayaran']"
                :value="request('status')" onchange="this.form.requestSubmit()" data-fi-select />
        </div>
        <x-fi.search-input :value="request('search')" placeholder="Cari nama proyek atau lokasi..." class="col-span-2 sm:w-64" />
    </x-report.final-report.filter-bar>

    {{-- SECTION: Kartu Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-fi.stat label="Total Proyek" icon="building-office-2" color="primary" description="Rekap proyek yang dibuat">
            {{ number_format($summary['total_projects'], 0, ',', '.') }}
        </x-fi.stat>
        <x-fi.stat label="Total RAB" icon="calculator" size="sm" description="Nilai keseluruhan proyek">
            {{ $rupiah($summary['total_rab']) }}
        </x-fi.stat>
        <x-fi.stat label="Total Terbayar" icon="check-circle" color="success" size="sm"
            description="Bukti rekap + invoice tertaut (tanpa PPN)">
            {{ $rupiah($summary['total_paid']) }}
        </x-fi.stat>
        <x-fi.stat label="Total Sisa Tagihan" icon="clock" color="warning" size="sm" description="Belum dibayar">
            {{ $rupiah($summary['total_remaining']) }}
        </x-fi.stat>
        <x-fi.stat label="Proyek Lunas" icon="check-circle" color="success"
            :description="'dari ' . number_format($summary['total_projects'], 0, ',', '.') . ' proyek'">
            {{ number_format($summary['paid_projects'], 0, ',', '.') }}
        </x-fi.stat>
    </div>

    {{-- SECTION: Detail Tabel + Paginasi --}}
    <x-fi.section heading="Detail Rekap Proyek"
        description="Rekap nilai proyek, uang masuk, pembayaran, dan sisa tagihan per proyek." flush>
        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                        <x-fi.table.th>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th>Lokasi</x-fi.table.th>
                        <x-fi.table.th>Dibuat</x-fi.table.th>
                        <x-fi.table.th align="end">Total RAB</x-fi.table.th>
                        <x-fi.table.th align="end">Uang Masuk</x-fi.table.th>
                        <x-fi.table.th align="end">Terbayar</x-fi.table.th>
                        <x-fi.table.th align="end">Sisa Tagihan</x-fi.table.th>
                        <x-fi.table.th>Status & Progress</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($recaps as $recap)
                        @php
                            $recapPaid = $recap->getTotalPaidAmount();
                            $recapProgress = $recap->getProgressPercent();
                            $recapRemaining = $recap->getRemainingAmount();

                            // Lunas → hijau; sebagian → kuning; belum ada pembayaran → merah
                            if ($recap->isFullyPaid()) {
                                $recapStatusLabel = 'Sudah Lunas';
                                $recapStatusColor = 'success';
                                $recapProgressColor = 'bg-emerald-500';
                            } elseif ($recapPaid > 0) {
                                $recapStatusLabel = 'Sebagian';
                                $recapStatusColor = 'warning';
                                $recapProgressColor = 'bg-amber-400';
                            } else {
                                $recapStatusLabel = 'Belum Ada';
                                $recapStatusColor = 'danger';
                                $recapProgressColor = 'bg-red-400';
                            }
                        @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50">
                            <x-fi.table.td align="center" class="tabular-nums text-gray-500">{{ $recaps->firstItem() + $loop->index }}</x-fi.table.td>

                            <x-fi.table.td class="min-w-[11rem] max-w-xs whitespace-normal">
                                <button type="button" onclick="openModal('recapDetailModal-{{ $recap->id }}')"
                                    class="text-start font-medium text-primary hover:underline" title="Lihat riwayat pembayaran">
                                    {{ $recap->project_name }}
                                </button>
                                @if ($recap->rab_number)
                                    <div class="text-xs text-gray-500">No RAB: {{ $recap->rab_number }}</div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td class="max-w-[10rem] whitespace-normal text-gray-700">{{ $recap->location ?: '-' }}</x-fi.table.td>

                            <x-fi.table.td class="text-gray-700">
                                {{ $recap->created_at ? \Carbon\Carbon::parse($recap->created_at)->format('d M Y') : '-' }}
                            </x-fi.table.td>

                            <x-fi.table.td align="end" class="font-medium tabular-nums">{{ $rupiah($recap->getTotalAmount()) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="tabular-nums">{{ $rupiah($recap->getDpAmount()) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="font-medium tabular-nums text-emerald-600">{{ $rupiah($recapPaid) }}</x-fi.table.td>
                            <x-fi.table.td align="end" :class="'font-semibold tabular-nums ' . ($recapRemaining > 0 ? 'text-amber-600' : 'text-emerald-600')">
                                {{ $rupiah($recapRemaining) }}
                            </x-fi.table.td>

                            {{-- Status + progress pembayaran --}}
                            <x-fi.table.td>
                                <div class="grid w-28 gap-y-1.5">
                                    <x-fi.badge :color="$recapStatusColor" class="justify-self-start">{{ $recapStatusLabel }}</x-fi.badge>
                                    <div class="flex items-center gap-1.5" title="Progress pembayaran {{ $recapProgress }}%">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-200">
                                            <div class="h-full rounded-full {{ $recapProgressColor }}" style="width: {{ $recapProgress }}%"></div>
                                        </div>
                                        <span class="text-xs font-medium tabular-nums text-gray-500">{{ $recapProgress }}%</span>
                                    </div>
                                </div>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $recap->project_name }}" width="w-60" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="eye" onclick="openModal('recapDetailModal-{{ $recap->id }}')">
                                            Lihat Detail
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="document-text" :href="route('project-financial-report.export.pdf', $recap)"
                                            data-preview data-preview-title="Laporan Keuangan {{ $recap->project_name }} (PDF)">
                                            Export PDF
                                        </x-fi.dropdown.item>
                                        <x-fi.dropdown.item icon="table-cells" :href="route('project-financial-report.export.excel', $recap)"
                                            data-preview data-preview-title="Laporan Keuangan {{ $recap->project_name }} (Excel)">
                                            Export Excel
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-fi.empty-state icon="inbox" heading="Tidak ada data rekap proyek untuk filter ini" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
            <x-fi.pagination :paginator="$recaps" />
        </div>
    </x-fi.section>
</div>

{{-- SECTION: Modal Riwayat Pembayaran per Proyek --}}
@foreach ($recaps as $recap)
    @php
        $recapProofs = $recap->paymentProofs;
        $recapTotalPaid = $recap->getTotalPaidAmount();
        $recapIncomePayments = $recap->getIncomePayments();
        $recapRemaining = $recap->getRemainingAmount();
    @endphp

    <x-fi.modal id="recapDetailModal-{{ $recap->id }}" heading="Riwayat Pembayaran — {{ $recap->project_name }}"
        :description="$recap->id . ($recap->location ? ' (' . $recap->location . ')' : '')" icon="clipboard-document-list"
        width="6xl">
        <div class="grid gap-y-6">

            {{-- Keterangan + Export (kanan) --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500">Daftar bukti pembayaran (uang masuk) pada proyek ini.</p>
                <div class="flex flex-wrap items-center gap-2">
                    <x-fi.button size="sm" color="gray" icon="document-text"
                        :href="route('project-financial-report.export.pdf', $recap)" data-preview
                        data-preview-title="Laporan Keuangan {{ $recap->project_name }} (PDF)">PDF</x-fi.button>
                    <x-fi.button size="sm" color="gray" icon="table-cells"
                        :href="route('project-financial-report.export.excel', $recap)" data-preview
                        data-preview-title="Laporan Keuangan {{ $recap->project_name }} (Excel)">Excel</x-fi.button>
                </div>
            </div>

            {{-- Ringkasan --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total RAB</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums text-gray-950">{{ $rupiah($recap->getTotalAmount()) }}</p>
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total Terbayar</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums text-emerald-600">{{ $rupiah($recapTotalPaid) }}</p>
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Sisa Tagihan</p>
                    <p @class(['mt-1 text-lg font-semibold tabular-nums', 'text-amber-600' => $recapRemaining > 0, 'text-emerald-600' => $recapRemaining <= 0])>
                        {{ $rupiah($recapRemaining) }}
                    </p>
                </div>
            </div>

            {{-- Daftar Bukti Pembayaran --}}
            <div class="grid gap-y-3">
                <h3 class="text-sm font-semibold text-gray-950">Daftar Pembayaran ({{ $recapProofs->count() }})</h3>

                @if ($recapProofs->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                        Belum ada bukti pembayaran pada proyek ini.
                    </div>
                @else
                    <div class="relative overflow-x-auto rounded-xl ring-1 ring-gray-950/10">
                        <table class="w-full table-auto divide-y divide-gray-200 text-start">
                            <thead class="bg-gray-50">
                                <tr>
                                    <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                                    <x-fi.table.th>Tanggal</x-fi.table.th>
                                    <x-fi.table.th align="end">Nominal</x-fi.table.th>
                                    <x-fi.table.th>Tahap</x-fi.table.th>
                                    <x-fi.table.th align="center">Bukti</x-fi.table.th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                                @foreach ($recapProofs as $index => $proof)
                                    <tr class="bg-white">
                                        <x-fi.table.td align="center" class="tabular-nums text-gray-500">{{ $index + 1 }}</x-fi.table.td>
                                        <x-fi.table.td class="text-gray-700">
                                            {{ $proof->payment_date ? \Carbon\Carbon::parse($proof->payment_date)->format('d M Y') : '-' }}
                                        </x-fi.table.td>
                                        <x-fi.table.td align="end" class="font-semibold tabular-nums text-emerald-600">{{ $rupiah((int) $proof->amount) }}</x-fi.table.td>
                                        <x-fi.table.td>{{ $proof->payment_stage ? 'Tahap ' . $proof->payment_stage : '-' }}</x-fi.table.td>
                                        <x-fi.table.td align="center">
                                            @if ($proof->file_path)
                                                {{-- Pratinjau bukti di dalam halaman (tanpa tab baru) --}}
                                                <a href="{{ asset('storage/' . $proof->file_path) }}"
                                                    onclick="event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti Pembayaran — ' . $recap->project_name), downloadName: @js($proof->file_name ?: basename($proof->file_path)) })"
                                                    title="{{ $proof->file_name }}"
                                                    class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                                                    <x-fi.icon name="paper-clip" class="h-4 w-4" /> File
                                                </a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </x-fi.table.td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Daftar Uang Masuk (Laporan Keuangan) --}}
            @if ($recapIncomePayments->isNotEmpty())
                <div class="grid gap-y-3">
                    <h3 class="text-sm font-semibold text-gray-950">Uang Masuk (Laporan Keuangan) ({{ $recapIncomePayments->count() }})</h3>
                    <div class="relative overflow-x-auto rounded-xl ring-1 ring-gray-950/10">
                        <table class="w-full table-auto divide-y divide-gray-200 text-start">
                            <thead class="bg-gray-50">
                                <tr>
                                    <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                                    <x-fi.table.th>Tanggal</x-fi.table.th>
                                    <x-fi.table.th>Kategori</x-fi.table.th>
                                    <x-fi.table.th>Keterangan</x-fi.table.th>
                                    <x-fi.table.th align="end">Nominal</x-fi.table.th>
                                    <x-fi.table.th align="center">Bukti</x-fi.table.th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                                @foreach ($recapIncomePayments as $index => $income)
                                    <tr class="bg-white">
                                        <x-fi.table.td align="center" class="tabular-nums text-gray-500">{{ $index + 1 }}</x-fi.table.td>
                                        <x-fi.table.td class="text-gray-700">
                                            {{ $income->transaction_date ? \Carbon\Carbon::parse($income->transaction_date)->format('d M Y') : '-' }}
                                        </x-fi.table.td>
                                        <x-fi.table.td>{{ $income->category?->name ?? '-' }}</x-fi.table.td>
                                        <x-fi.table.td class="min-w-[12rem] whitespace-normal text-gray-700">{{ $income->description ?: '-' }}</x-fi.table.td>
                                        <x-fi.table.td align="end" class="font-semibold tabular-nums text-emerald-600">{{ $rupiah((int) $income->income_amount) }}</x-fi.table.td>
                                        <x-fi.table.td align="center">
                                            @if ($income->proof_file)
                                                {{-- Pratinjau bukti di dalam halaman (tanpa tab baru) --}}
                                                <a href="{{ asset('storage/' . $income->proof_file) }}"
                                                    onclick="event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti Uang Masuk — ' . $recap->project_name), downloadName: @js($income->proof_file_name ?: basename($income->proof_file)) })"
                                                    title="{{ $income->proof_file_name }}"
                                                    class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                                                    <x-fi.icon name="paper-clip" class="h-4 w-4" /> File
                                                </a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </x-fi.table.td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </x-fi.modal>
@endforeach
