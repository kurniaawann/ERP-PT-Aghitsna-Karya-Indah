{{-- =====================================================================
     Halaman: Rekap Pengeluaran (Expense Recaps)
     Tujuan: Menampilkan daftar & grand totals rekap pengeluaran (income,
             expense, balance) dengan filter kategori/bulan/tahun, search,
             CRUD manual, dan export Excel/PDF.
     Data dari RecapExpenseController@index:
     - $expenseRecaps : Paginator ExpenseRecap (10/halaman) dari
                        RecapExpenseService::buildIndexQuery($request),
                        difilter oleh category, month, year, search.
     - $categories    : Daftar kategori pengeluaran dari
                        RecapExpenseService::getExpenseCategories(),
                        dipakai di filter select & dropdown modal.
     - $totals        : Grand totals (total_income, total_expense, balance)
                        hasil RecapExpenseService::getGrandTotals($request).
     Komponen yang di-include:
     - x-filters.select-filter / month-filter / year-filter / search-input : toolbar filter
     - x-buttons.print-dropdown / delete-button / add-button               : tombol aksi
     - components.finance.expense-recaps.table                            : tabel rekap
     - components.finance.expense-recaps.add-modal / edit-modal           : modal CRUD
     - x-modal                                                            : konfirmasi hapus
     - x-pagination                                                       : navigasi halaman
     JS: @vite('resources/js/pages/finance/expense-recaps/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Pengeluaran')

@section('content')
    {{-- ==================== Kontainer Utama Halaman ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        {{-- ==================== Section: Header Rekap Pengeluaran ==================== --}}
        {{-- Header Rekap Pengeluaran --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Pengeluaran</h1>

        {{-- Toolbar: Filter + Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('recap-expense.index') }}"
                class="w-full min-[1520px]:w-auto min-[1520px]:flex-1 flex flex-col min-[1520px]:flex-row gap-3">

                {{-- Filter Kategori --}}
                <x-filters.select-filter name="category" :value="request('category')" :options="$categories" placeholder="Semua Kategori" responsive="custom" />

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" responsive="custom" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" responsive="custom" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari transaksi..." responsive="custom" />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 min-[1520px]:mt-0 w-full min-[1520px]:w-auto">
                <div class="flex flex-col min-[1520px]:flex-row gap-2 w-full min-[1520px]:w-auto">
                    {{-- Tombol Export --}}
                    <x-buttons.print-dropdown :excelRoute="route('recap-expense.export.excel')" :pdfRoute="route('recap-expense.export.pdf')" :queryParams="[
                        'search' => request('search'),
                        'category' => request('category'),
                        'month' => request('month'),
                        'year' => request('year'),
                    ]" responsive="custom" />

                    {{-- Tombol Hapus --}}
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                    {{-- Tombol Tambah --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Pengeluaran" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Section: Tabel Rekap Pengeluaran ==================== --}}
        {{-- Tabel Rekap Pengeluaran --}}
        {{-- Tabel rekap; menerima $totals untuk kolom total di akhir tabel.
             Data auto-generated (dari sales report) ikut ditampilkan di sini. --}}
        @include('components.finance.expense-recaps.table', [
            'expenseRecaps' => $expenseRecaps,
            'totals' => $totals,
        ])
    </div>

    {{-- ==================== Section: Pagination ==================== --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$expenseRecaps" />

    {{-- ==================== Section: Modals ==================== --}}
    {{-- Modal Tambah --}}
    @include('components.finance.expense-recaps.add-modal', ['categories' => $categories])

    {{-- Modal Edit untuk setiap expense (hanya yang manual) --}}
    {{-- Modal Edit untuk setiap expense. Hanya dirender untuk data manual:
         data auto-generated dari sales report tidak boleh diedit, karena
         sumber datanya berasal dari modul lain. --}}
    @foreach ($expenseRecaps as $expense)
        @if (!$expense->isAutoGenerated())
            @include('components.finance.expense-recaps.edit-modal', ['expense' => $expense, 'categories' => $categories])
        @endif
    @endforeach

    {{-- ==================== Section: Modal Konfirmasi Bulk Delete ==================== --}}
    {{-- Modal Hapus --}}
    {{-- Catatan: data yang auto-generated dari sales report tidak akan
         dihapus oleh bulk delete. --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        <p class="text-text-primary mb-4">Apakah Anda yakin ingin menghapus data yang dipilih?</p>
        <p class="text-sm text-text-secondary">
            <i class="fa-solid fa-info-circle"></i> Data yang auto-generated dari sales report tidak akan dihapus.
        </p>
    </x-modal>

    {{-- ==================== Section: JavaScript ==================== --}}
    {{-- JavaScript --}}
    @vite(['resources/js/pages/finance/expense-recaps/index.js'])
@endsection
