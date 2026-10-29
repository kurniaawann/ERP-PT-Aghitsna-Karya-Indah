{{-- =====================================================================
     Partial: Laporan Keuangan Proyek (dalam Laporan Akhir, gaya Filament)
     Menampilkan rekap uang masuk, uang keluar, dan saldo per proyek
     berdasarkan transaksi "Bon" pada Laporan Keuangan Proyek masing-masing
     rekap. Dilengkapi ringkasan dan menu Aksi "Lihat Detail" untuk melihat
     rincian transaksi per proyek (modal).
     Form disubmit ke route report.final dengan tab=financial agar tetap
     berada di halaman Laporan Akhir.
     Data dari FinalReportService::buildFinancialData:
     - $recaps : paginator Rekap Proyek (relasi financialReport.items)
     - $summary: ringkasan seluruh data yang lolos filter
     ===================================================================== --}}
@php
    $rupiah = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');
    $bonDescription = 'Seluruh baris "Bon" tercatat';
@endphp

<div class="flex flex-col gap-y-6">

    {{-- SECTION: Filter (bulan, tahun, status laporan, pencarian) --}}
    <x-report.final-report.filter-bar tab="financial" formId="financialFilterForm">
        <div class="sm:w-44">
            <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="sm:w-36">
            <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="col-span-2 sm:w-52">
            <x-fi.select name="status" aria-label="Filter Status" placeholder="Semua Status"
                :options="['with_transactions' => 'Sudah Ada Transaksi', 'without_transactions' => 'Belum Ada Transaksi']"
                :value="request('status')" onchange="this.form.requestSubmit()" data-fi-select />
        </div>
        <x-fi.search-input :value="request('search')" placeholder="Cari nama proyek atau lokasi..." class="col-span-2 sm:w-64" />
    </x-report.final-report.filter-bar>

    {{-- SECTION: Kartu Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-fi.stat label="Total Proyek" icon="building-office-2" color="primary" description="Proyek yang tercatat">
            {{ number_format($summary['total_projects'], 0, ',', '.') }}
        </x-fi.stat>
        <x-fi.stat label="Total Uang Masuk" icon="arrow-down" color="success" size="sm" description="Total pendapatan proyek">
            {{ $rupiah($summary['total_income']) }}
        </x-fi.stat>
        <x-fi.stat label="Total Uang Keluar" icon="arrow-up" color="danger" size="sm" description="Total pengeluaran proyek">
            {{ $rupiah($summary['total_expense']) }}
        </x-fi.stat>
        <x-fi.stat label="Total Saldo" icon="scale" :color="$summary['total_balance'] >= 0 ? 'primary' : 'danger'" size="sm"
            :description="$summary['total_balance'] >= 0 ? 'Saldo surplus' : 'Saldo minus (defisit)'">
            {{ $rupiah(abs($summary['total_balance'])) }}
        </x-fi.stat>
        <x-fi.stat label="Total Transaksi" icon="document-text" :description="$bonDescription">
            {{ number_format($summary['total_transactions'], 0, ',', '.') }}
        </x-fi.stat>
    </div>

    {{-- SECTION: Detail Tabel + Paginasi --}}
    <x-fi.section heading="Detail Keuangan per Proyek"
        description="Ringkasan uang masuk, uang keluar, dan saldo per proyek. Pilih “Lihat Detail” untuk melihat rincian transaksi." flush>
        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                        <x-fi.table.th>Nama Proyek</x-fi.table.th>
                        <x-fi.table.th>Lokasi</x-fi.table.th>
                        <x-fi.table.th>Dibuat</x-fi.table.th>
                        <x-fi.table.th align="end">Uang Masuk</x-fi.table.th>
                        <x-fi.table.th align="end">Uang Keluar</x-fi.table.th>
                        <x-fi.table.th align="end">Saldo</x-fi.table.th>
                        <x-fi.table.th align="center">Transaksi</x-fi.table.th>
                        <x-fi.table.th>Status</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($recaps as $recap)
                        @php
                            $finIncome = (int) $recap->fin_income;
                            $finExpense = (int) $recap->fin_expense;
                            $finBalance = (int) $recap->fin_balance;
                            $finTransactions = (int) $recap->fin_transactions;
                        @endphp
                        <tr class="bg-white transition duration-75 hover:bg-gray-50">
                            <x-fi.table.td align="center" class="tabular-nums text-gray-500">{{ $recaps->firstItem() + $loop->index }}</x-fi.table.td>

                            <x-fi.table.td class="min-w-[11rem] max-w-xs whitespace-normal">
                                @if ($finTransactions > 0)
                                    <button type="button" onclick="openModal('finDetailModal-{{ $recap->id }}')"
                                        class="text-start font-medium text-primary hover:underline" title="Lihat rincian transaksi">
                                        {{ $recap->project_name }}
                                    </button>
                                @else
                                    <div class="font-medium">{{ $recap->project_name }}</div>
                                @endif
                                @if ($recap->rab_number)
                                    <div class="text-xs text-gray-500">No RAB: {{ $recap->rab_number }}</div>
                                @endif
                            </x-fi.table.td>

                            <x-fi.table.td class="max-w-[10rem] whitespace-normal text-gray-700">{{ $recap->location ?: '-' }}</x-fi.table.td>

                            <x-fi.table.td class="text-gray-700">
                                {{ $recap->created_at ? \Carbon\Carbon::parse($recap->created_at)->format('d M Y') : '-' }}
                            </x-fi.table.td>

                            <x-fi.table.td align="end" class="font-medium tabular-nums text-emerald-600">{{ $rupiah($finIncome) }}</x-fi.table.td>
                            <x-fi.table.td align="end" class="font-medium tabular-nums text-red-600">{{ $rupiah($finExpense) }}</x-fi.table.td>
                            <x-fi.table.td align="end" :class="'font-semibold tabular-nums ' . ($finBalance >= 0 ? 'text-primary' : 'text-red-600')">
                                {{ $rupiah($finBalance) }}
                            </x-fi.table.td>

                            <x-fi.table.td align="center">
                                <span class="inline-flex items-center gap-1 font-medium tabular-nums">
                                    <x-fi.icon name="document-text" class="h-4 w-4 text-gray-400" />
                                    {{ number_format($finTransactions, 0, ',', '.') }}
                                </span>
                            </x-fi.table.td>

                            <x-fi.table.td>
                                @if ($finTransactions > 0)
                                    <x-fi.badge color="success">Ada Transaksi</x-fi.badge>
                                @else
                                    <x-fi.badge color="danger">Belum Ada</x-fi.badge>
                                @endif
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ (detail hanya bila sudah ada transaksi) --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $recap->project_name }}" width="w-60" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="eye" onclick="openModal('finDetailModal-{{ $recap->id }}')"
                                            :disabled="$finTransactions === 0" :hint="$finTransactions === 0 ? 'Belum ada transaksi' : null">
                                            Lihat Detail
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-fi.empty-state icon="inbox" heading="Tidak ada data laporan keuangan proyek untuk filter ini" />
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

{{-- SECTION: Modal Rincian Transaksi per Proyek --}}
@foreach ($recaps as $recap)
    @php
        $finItems = optional($recap->financialReport)->items ?? collect();
        $finItems = $finItems->sortBy('transaction_date')->values();
        $finTotalIncome = (int) $finItems->where('is_informational', false)->sum('income_amount');
        $finTotalExpense = (int) $finItems->where('is_informational', false)->sum('expense_amount');
        $finTotalBalance = $finTotalIncome - $finTotalExpense;
    @endphp

    <x-fi.modal id="finDetailModal-{{ $recap->id }}" heading="Rincian Keuangan — {{ $recap->project_name }}"
        :description="$recap->id . ($recap->location ? ' (' . $recap->location . ')' : '')" icon="document-currency-dollar"
        width="6xl">
        <div class="grid gap-y-6">

            {{-- Keterangan + nomor Laporan Keuangan --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500">Rincian transaksi "Bon" pada proyek ini.</p>
                @if ($recap->financialReport)
                    <x-fi.badge color="primary" icon="document-currency-dollar">{{ $recap->financialReport->id }}</x-fi.badge>
                @endif
            </div>

            {{-- Ringkasan Finansial --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total Uang Masuk</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums text-emerald-600">{{ $rupiah($finTotalIncome) }}</p>
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total Uang Keluar</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums text-red-600">{{ $rupiah($finTotalExpense) }}</p>
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Saldo</p>
                    <p @class(['mt-1 text-lg font-semibold tabular-nums', 'text-primary' => $finTotalBalance >= 0, 'text-red-600' => $finTotalBalance < 0])>
                        {{ $rupiah($finTotalBalance) }}
                    </p>
                </div>
            </div>

            {{-- Daftar Transaksi --}}
            <div class="grid gap-y-3">
                <h3 class="text-sm font-semibold text-gray-950">Daftar Transaksi ({{ $finItems->count() }})</h3>

                @if ($finItems->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                        Belum ada transaksi pada proyek ini.
                    </div>
                @else
                    <div class="relative overflow-x-auto rounded-xl ring-1 ring-gray-950/10">
                        <table class="w-full table-auto divide-y divide-gray-200 text-start">
                            <thead class="bg-gray-50">
                                <tr>
                                    <x-fi.table.th align="center" class="w-1">No</x-fi.table.th>
                                    <x-fi.table.th>Tanggal</x-fi.table.th>
                                    <x-fi.table.th>Kategori</x-fi.table.th>
                                    <x-fi.table.th>Keterangan</x-fi.table.th>
                                    <x-fi.table.th align="end">Jumlah</x-fi.table.th>
                                    <x-fi.table.th>Keterangan Bon</x-fi.table.th>
                                    <x-fi.table.th align="center">Bukti</x-fi.table.th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                                @foreach ($finItems as $index => $item)
                                    @php
                                        $isIncome = (int) ($item->income_amount ?? 0) > 0;
                                        $amount = (int) ($item->income_amount ?: $item->expense_amount);
                                    @endphp
                                    <tr class="bg-white">
                                        <x-fi.table.td align="center" class="tabular-nums text-gray-500">{{ $index + 1 }}</x-fi.table.td>
                                        <x-fi.table.td class="text-gray-700">
                                            {{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('d M Y') : '-' }}
                                        </x-fi.table.td>
                                        <x-fi.table.td class="whitespace-normal">
                                            <span class="inline-flex flex-wrap items-center gap-1.5">
                                                {{ $item->category->name ?? '-' }}
                                                @if ($item->is_informational)
                                                    <x-fi.badge color="primary" icon="information-circle"
                                                        title="Baris informasi — tidak memengaruhi total laporan">Info</x-fi.badge>
                                                @endif
                                            </span>
                                        </x-fi.table.td>
                                        <x-fi.table.td class="min-w-[12rem] whitespace-normal">{{ $item->description }}</x-fi.table.td>
                                        <x-fi.table.td align="end" :class="'font-semibold tabular-nums ' . ($isIncome ? 'text-emerald-600' : 'text-red-600')">
                                            @if ($item->is_informational)
                                                <span class="text-gray-400">-</span>
                                            @else
                                                {{ $isIncome ? '+' : '-' }} {{ $rupiah($amount) }}
                                            @endif
                                        </x-fi.table.td>
                                        <x-fi.table.td class="min-w-[10rem] whitespace-normal text-gray-700">{{ $item->keterangan_bon ?: '-' }}</x-fi.table.td>
                                        <x-fi.table.td align="center">
                                            @if ($item->hasProof())
                                                {{-- Pratinjau bukti di dalam halaman (tanpa tab baru) --}}
                                                <a href="{{ asset('storage/' . $item->proof_file) }}"
                                                    onclick="event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti — ' . ($item->description ?: 'Transaksi')), downloadName: @js($item->proof_file_name ?: basename($item->proof_file)) })"
                                                    title="{{ $item->proof_file_name }}"
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
        </div>
    </x-fi.modal>
@endforeach
