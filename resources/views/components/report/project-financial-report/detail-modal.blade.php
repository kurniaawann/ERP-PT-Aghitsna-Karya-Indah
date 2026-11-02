{{-- ==================== Modal Detail Laporan Keuangan Proyek (gaya Filament) ==================== --}}
@props(['recap'])

@php
    $report = $recap->financialReport;
    $items = optional($report)->items ?? collect();
    $financialItems = $items->where('is_informational', false);

    $totalIncome = (int) $financialItems->sum('income_amount');
    $totalExpense = (int) $financialItems->sum('expense_amount');
    $totals = (object) [
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'balance' => $totalIncome - $totalExpense,
    ];

    $rupiah = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
@endphp

<x-fi.modal id="detailModal-{{ $recap->id }}" heading="Laporan Keuangan — {{ $recap->project_name }}"
    :description="$recap->id . ($recap->location ? ' (' . $recap->location . ')' : '')" icon="document-currency-dollar"
    width="6xl">
    <div class="grid gap-y-6">

        {{-- Keterangan (kiri) + nomor Laporan Keuangan & tombol Edit (kanan) --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Detail transaksi "Bon" pada proyek ini.</p>
            <div class="flex flex-wrap items-center gap-2">
                <x-fi.badge color="primary" icon="document-currency-dollar">{{ $report->id ?? 'Laporan belum dibuat' }}</x-fi.badge>
                <x-fi.button size="sm" color="gray" icon="pencil-square" :href="route('project-financial-report.edit', $recap)"
                    title="Edit Rekap Proyek & Transaksi">
                    Edit
                </x-fi.button>
            </div>
        </div>

        {{-- Ringkasan Finansial --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                <p class="text-sm text-gray-500">Total Uang Masuk</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-emerald-600">{{ $rupiah($totals->total_income) }}</p>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                <p class="text-sm text-gray-500">Total Uang Keluar</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-red-600">{{ $rupiah($totals->total_expense) }}</p>
            </div>
            <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                <p class="text-sm text-gray-500">Saldo</p>
                <p @class(['mt-1 text-lg font-semibold tabular-nums', 'text-primary' => $totals->balance >= 0, 'text-red-600' => $totals->balance < 0])>
                    {{ $rupiah($totals->balance) }}
                </p>
            </div>
        </div>

        {{-- Daftar Transaksi --}}
        <div class="grid gap-y-3">
            <h3 class="text-sm font-semibold text-gray-950">Daftar Transaksi ({{ $items->count() }})</h3>

            @if ($items->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                    Belum ada transaksi. Klik "Edit" untuk menambah.
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
                            @foreach ($items as $index => $item)
                                @php
                                    $isIncome = (int) ($item->income_amount ?? 0) > 0;
                                    $amount = (int) ($item->income_amount ?: $item->expense_amount);
                                @endphp
                                <tr class="bg-white">
                                    <x-fi.table.td align="center" class="tabular-nums text-gray-500">{{ $index + 1 }}</x-fi.table.td>
                                    <x-fi.table.td class="text-gray-700">
                                        {{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') : '-' }}
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
