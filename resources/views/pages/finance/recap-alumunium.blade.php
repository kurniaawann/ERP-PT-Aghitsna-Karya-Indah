@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Alumunium')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Alumunium</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('recap-alumunium.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" />
                <x-filters.year-filter :value="request('year')" />
                <x-filters.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau proyek..." />
            </form>

            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('recap-alumunium.export.excel')" :pdfRoute="route('recap-alumunium.export.pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                    ]" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Total Invoice</p>
                <p class="text-xl font-bold text-text-primary mt-1">Rp
                    {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Sudah Dibayar</p>
                <p class="text-xl font-bold text-success mt-1">Rp {{ number_format($totals->total_paid ?? 0, 0, ',', '.') }}
                </p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Sisa Tagihan</p>
                <p class="text-xl font-bold text-warning mt-1">Rp
                    {{ number_format($totals->total_remaining ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Lunas</p>
                <p class="text-xl font-bold text-success mt-1">{{ $totals->paid_count ?? 0 }} invoice</p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="text-xs uppercase tracking-wide text-text-secondary">Belum Lunas</p>
                <p class="text-xl font-bold text-error mt-1">{{ $totals->unpaid_count ?? 0 }} invoice</p>
            </div>
        </div>

        <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="inline-block min-w-full align-middle">
                <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="p-2 text-center">No</th>
                                <th class="p-2 text-left">No Invoice</th>
                                <th class="p-2 text-left">Tanggal</th>
                                <th class="p-2 text-left">Kepada</th>
                                <th class="p-2 text-left">Proyek</th>
                                <th class="p-2 text-center">Total Invoice</th>
                                <th class="p-2 text-center">Sudah Dibayar</th>
                                <th class="p-2 text-center">Sisa</th>
                                <th class="p-2 text-center">Status</th>
                                <th class="p-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $index => $invoice)
                                <tr class="border-t hover:bg-surface-secondary">
                                    <td class="p-2 text-center">{{ $invoices->firstItem() + $index }}</td>
                                    <td class="p-2 font-medium text-primary">{{ $invoice->invoice_number }}</td>
                                    <td class="p-2 text-sm">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                                    <td class="p-2">{{ $invoice->recipient }}</td>
                                    <td class="p-2 text-sm text-text-label">
                                        {{ $invoice->project_description ? \Illuminate\Support\Str::limit($invoice->project_description, 35) : '-' }}
                                    </td>
                                    <td class="p-2 text-right font-medium">Rp
                                        {{ number_format($invoice->getNetAmount(), 0, ',', '.') }}</td>
                                    <td class="p-2 text-right font-medium text-success">Rp
                                        {{ number_format($invoice->getTotalPaidAmount(), 0, ',', '.') }}</td>
                                    <td class="p-2 text-right font-medium text-warning">Rp
                                        {{ number_format($invoice->getRemainingAmount(), 0, ',', '.') }}</td>
                                    <td class="p-2 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $invoice->payment_status_badge_class }}">
                                            {{ $invoice->payment_status_label }}
                                        </span>
                                    </td>
                                    <td class="p-2 text-center">
                                        <button type="button"
                                            onclick="openModal('detailModal-{{ $invoice->invoice_number }}')"
                                            class="inline-flex items-center gap-1 bg-info hover:bg-info/90 text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Lihat Detail">
                                            <i class="fa-solid fa-eye w-3 h-3"></i>
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center p-4 text-text-secondary">Data rekap alumunium
                                        tidak ditemukan.</td>
                                </tr>
                            @endforelse

                            @if ($invoices->isNotEmpty())
                                <tr
                                    class="bg-gradient-to-r from-primary/20 to-primary/10 border-t-4 border-primary font-bold text-base">
                                    <td colspan="5" class="p-3 text-right text-text-heading">TOTAL REKAP ALUMUNIUM</td>
                                    <td class="p-3 text-right text-text-heading">Rp
                                        {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</td>
                                    <td class="p-3 text-right text-text-heading">Rp
                                        {{ number_format($totals->total_paid ?? 0, 0, ',', '.') }}</td>
                                    <td class="p-3 text-right text-text-heading">Rp
                                        {{ number_format($totals->total_remaining ?? 0, 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <x-pagination :paginator="$invoices" />
    </div>

    @foreach ($invoices as $invoice)
        @include('components.finance.alumunium.detail-modal', ['invoice' => $invoice])
    @endforeach
@endsection
