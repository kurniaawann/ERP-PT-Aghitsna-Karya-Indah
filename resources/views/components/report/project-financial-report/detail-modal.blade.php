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
        <div class="text-right">
            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-primary-light text-primary gap-1">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                {{ $report->id ?? 'Laporan belum dibuat' }}
            </span>
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

</x-modal>
