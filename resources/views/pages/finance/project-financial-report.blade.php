{{-- =====================================================================
     Halaman: Laporan Keuangan Proyek
     Tujuan: Menampilkan laporan keuangan (uang masuk / uang keluar) per
             Rekap Proyek. Setiap rekap memiliki satu laporan (auto-create
             saat dibuka pertama kali dari tombol di tabel Rekap Proyek).
             Terdiri dari ringkasan finansial, daftar "Bon" yang diinput
             manual (kategori, tanggal, keterangan, nominal, keterangan bon,
             bukti pembayaran), dan ekspor PDF/Excel.
     Data dari ProjectFinancialReportController@show:
     - $recap      : ProjectRecap (rekap proyek pemilik laporan)
     - $report     : ProjectFinancialReport (laporan, auto-created)
     - $items      : Collection item "Bon" terurut per kategori
     - $categories : Kategori transaksi modul project_finance
     - $totals     : Grand totals (total_income, total_expense, balance)
     Komponen yang di-include:
     - components.finance.project-financial-report.table
     - components.finance.project-financial-report.add-modal
     - components.finance.project-financial-report.edit-modal (per item)
     - x-modal (konfirmasi hapus massal)
     JS: @vite('resources/js/pages/finance/project-financial-report/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Keuangan Proyek')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        {{-- ==================== Header + Info Rekap ==================== --}}
        <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="text-2xl font-semibold text-text-primary mb-1">Laporan Keuangan</h1>
                <p class="text-sm text-text-secondary">
                    {{ $recap->id }} — {{ $recap->project_name }}
                    @if ($recap->location)
                        <span class="text-text-label">({{ $recap->location }})</span>
                    @endif
                </p>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-primary-light text-primary gap-1">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    {{ $report->id }}
                </span>
            </div>
        </div>

        {{-- ==================== Ringkasan Finansial ==================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <div class="rounded-xl border border-border-strong bg-white p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Total Uang Masuk</p>
                <p class="text-xl font-bold text-success">Rp {{ number_format($totals->total_income ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong bg-white p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Total Uang Keluar</p>
                <p class="text-xl font-bold text-error">Rp {{ number_format($totals->total_expense ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong bg-white p-4 shadow-sm">
                <p class="text-xs text-text-secondary mb-1">Saldo</p>
                <p class="text-xl font-bold {{ ($totals->balance ?? 0) >= 0 ? 'text-primary' : 'text-error' }}">
                    Rp {{ number_format($totals->balance ?? 0, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- ==================== Toolbar ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm text-text-secondary">
                Daftar transaksi "Bon" pada proyek
                <span class="font-semibold text-text-primary">{{ $recap->project_name }}</span>.
            </p>

            <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                <x-buttons.print-dropdown
                    :excelRoute="route('project-financial-report.export.excel', $recap)"
                    :pdfRoute="route('project-financial-report.export.pdf', $recap)"
                    responsive="custom" />

                <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                <x-buttons.add-button modalId="addModal" text="Tambah Transaksi" responsive="custom" />
            </div>
        </div>

        {{-- ==================== Tabel Transaksi ==================== --}}
        @include('components.finance.project-financial-report.table', [
            'recap' => $recap,
            'items' => $items,
            'totals' => $totals,
        ])
    </div>

    {{-- ==================== Modals ==================== --}}
    @include('components.finance.project-financial-report.add-modal', [
        'recap' => $recap,
        'categories' => $categories,
    ])

    @foreach ($items as $item)
        @include('components.finance.project-financial-report.edit-modal', [
            'recap' => $recap,
            'item' => $item,
            'categories' => $categories,
        ])
    @endforeach

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        <p class="text-text-primary mb-4">Apakah Anda yakin ingin menghapus data transaksi yang dipilih?</p>
        <p class="text-sm text-text-secondary">
            <i class="fa-solid fa-info-circle"></i> File bukti pembayaran dari data yang dihapus juga akan ikut terhapus.
        </p>
    </x-modal>

    {{-- ==================== JavaScript ==================== --}}
    @vite(['resources/js/pages/finance/project-financial-report/index.js'])
@endsection
