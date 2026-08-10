{{-- ==================== Modal Detail Laporan Keuangan Proyek ==================== --}}
@props(['recap', 'categories'])

@php
    $report = $recap->financialReport;
    $items = optional($report)->items ?? collect();

    $totalIncome = (int) $items->sum('income_amount');
    $totalExpense = (int) $items->sum('expense_amount');
    $totals = (object) [
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'balance' => $totalIncome - $totalExpense,
    ];
@endphp

<x-modal id="detailModal-{{ $recap->id }}" title="Laporan Keuangan — {{ $recap->project_name }}"
    :hideFooter="true" size="6xl">

    {{-- ==================== Header Informasi ==================== --}}
    <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
        <div>
            <p class="text-sm text-text-secondary">
                {{ $recap->id }}
                @if ($recap->location)
                    <span class="text-text-label">({{ $recap->location }})</span>
                @endif
            </p>
            <p class="text-sm text-text-secondary mt-1">Detail transaksi "Bon" pada proyek ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-primary-light text-primary gap-1">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                {{ $report->id ?? 'Laporan belum dibuat' }}
            </span>
            <button type="button" onclick="openModal('editPfrModal-{{ $recap->id }}')"
                class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs"
                title="Edit Rekap Proyek & Transaksi">
                <i class="fa-solid fa-pen w-3 h-3"></i>
                Edit
            </button>
        </div>
    </div>

    {{-- ==================== Ringkasan Finansial ==================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-xl border border-border-strong bg-white p-4 shadow-sm">
            <p class="text-xs text-text-secondary mb-1">Total Uang Masuk</p>
            <p class="text-xl font-bold text-success">Rp {{ number_format($totals->total_income, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-border-strong bg-white p-4 shadow-sm">
            <p class="text-xs text-text-secondary mb-1">Total Uang Keluar</p>
            <p class="text-xl font-bold text-error">Rp {{ number_format($totals->total_expense, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-border-strong bg-white p-4 shadow-sm">
            <p class="text-xs text-text-secondary mb-1">Saldo</p>
            <p class="text-xl font-bold {{ $totals->balance >= 0 ? 'text-primary' : 'text-error' }}">
                Rp {{ number_format($totals->balance, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- ==================== Daftar Transaksi ==================== --}}
    <div class="mt-6">
        <h6 class="text-text-primary font-semibold mb-3">Daftar Transaksi ({{ $items->count() }})</h6>

        @if ($items->isEmpty())
            <div class="text-center p-4 border border-dashed border-border-strong rounded bg-surface-secondary">
                <p class="text-sm text-text-secondary">Belum ada transaksi. Klik "Edit Transaksi" untuk menambah.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                    <div class="border border-border-strong rounded-xl overflow-hidden shadow-sm">
                        <table class="min-w-full divide-y divide-border-light">
                            <thead class="bg-surface-secondary">
                                <tr>
                                    <th class="p-2 text-center">No</th>
                                    <th class="p-2 text-left">Tanggal</th>
                                    <th class="p-2 text-left">Kategori</th>
                                    <th class="p-2 text-left">Keterangan</th>
                                    <th class="p-2 text-right">Jumlah</th>
                                    <th class="p-2 text-left">Keterangan Bon</th>
                                    <th class="p-2 text-center">Bukti</th>
                                </tr>
                            </thead>
                            <tbody class="bg-surface-base divide-y divide-border-light">
                                @foreach ($items as $index => $item)
                                    @php
                                        $isIncome = (int) ($item->income_amount ?? 0) > 0;
                                        $amount = (int) ($item->income_amount ?: $item->expense_amount);
                                    @endphp
                                    <tr class="hover:bg-surface-secondary transition-colors duration-150">
                                        <td class="p-2 text-center font-medium text-primary">{{ $index + 1 }}</td>
                                        <td class="p-2 text-center text-text-secondary">
                                            {{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="p-2 text-text-primary">{{ $item->category->name ?? '-' }}</td>
                                        <td class="p-2 text-text-primary">{{ $item->description }}</td>
                                        <td class="p-2 text-right font-medium {{ $isIncome ? 'text-success' : 'text-error' }}">
                                            {{ $isIncome ? '+' : '-' }} Rp {{ number_format($amount, 0, ',', '.') }}
                                        </td>
                                        <td class="p-2 text-text-secondary">{{ $item->keterangan_bon ?: '-' }}</td>
                                        <td class="p-2 text-center">
                                            @if ($item->hasProof())
                                                <a href="{{ asset('storage/' . $item->proof_file) }}" target="_blank"
                                                    rel="noopener noreferrer" title="{{ $item->proof_file_name }}"
                                                    class="text-blue-600 hover:underline text-sm">
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
            </div>
        @endif
    </div>

</x-modal>
