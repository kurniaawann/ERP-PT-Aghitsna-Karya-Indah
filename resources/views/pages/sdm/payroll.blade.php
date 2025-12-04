@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Payroll')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Data Payroll</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('payroll.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari karyawan..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('payroll.export.excel')" :pdfRoute="route('payroll.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />

                    <button type="button" id="bulk-pay-button" onclick="openModal('bulkPayModal')"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium opacity-50 cursor-not-allowed"
                        disabled>
                        <i class="fa-solid fa-money-check-alt"></i>
                        Bayar Terpilih
                    </button>

                    <x-buttons.delete-button modalId="deleteModal" />

                    <button type="button" onclick="openModal('generateModal')"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
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
        <p class="text-gray-700">Apakah kamu yakin ingin membayar payroll yang dipilih?</p>
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.sdm.payroll.payroll-scripts')
@endsection
