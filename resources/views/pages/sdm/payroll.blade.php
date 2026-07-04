@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Payroll')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Payroll</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('payroll.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" />

                {{-- Filter Minggu --}}
                <select name="week_number"
                    class="border border-border-strong rounded-lg px-3 py-2 text-sm bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Semua Minggu</option>
                    <option value="1" {{ request('week_number') == 1 ? 'selected' : '' }}>Minggu 1</option>
                    <option value="2" {{ request('week_number') == 2 ? 'selected' : '' }}>Minggu 2</option>
                    <option value="3" {{ request('week_number') == 3 ? 'selected' : '' }}>Minggu 3</option>
                    <option value="4" {{ request('week_number') == 4 ? 'selected' : '' }}>Minggu 4</option>
                </select>

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari karyawan..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('payroll.export.excel')" :pdfRoute="route('payroll.export.pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                        'week_number' => request('week_number'),
                    ]" />

                    <button type="button" id="bulk-pay-button" onclick="openModal('bulkPayModal')"
                        class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium opacity-50 cursor-not-allowed"
                        disabled>
                        <i class="fa-solid fa-money-check-alt"></i>
                        Bayar Terpilih
                    </button>

                    <x-buttons.delete-button modalId="deleteModal" />

                    <button type="button" onclick="openModal('generateModal')"
                        class="flex items-center gap-2 bg-success hover:bg-success-hover text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
                        <i class="fa-solid fa-calculator"></i>
                        Generate Payroll
                    </button>
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.sdm.payroll.table', ['payrolls' => $payrolls])

        {{-- Pagination --}}
        <x-pagination :paginator="$payrolls" />
    </div>

    {{-- Modal Generate Payroll --}}
    @include('components.sdm.payroll.generate-modal')

    {{-- Hapus modal Bayar per-item: kini pembayaran hanya via Bulk Pay --}}

    {{-- Modal Detail untuk setiap payroll --}}
    @foreach ($payrolls as $payroll)
        @if ($payroll->status === 'draft')
            @include('components.sdm.payroll.edit-modal', ['payroll' => $payroll])
        @endif
        @include('components.sdm.payroll.detail-modal', ['payroll' => $payroll])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?

        (Hanya payroll dengan status Draft yang dapat dihapus)
    </x-modal>

    {{-- Modal Konfirmasi Bulk Pay --}}
    <x-modal id="bulkPayModal" title="Konfirmasi Bayar" method="PATCH" onConfirm="submitBulkPayForm()"
        buttonText="Ya, Bayar">
        <p class="text-text-primary">Apakah kamu yakin ingin membayar payroll yang dipilih?</p>
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.sdm.payroll-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
