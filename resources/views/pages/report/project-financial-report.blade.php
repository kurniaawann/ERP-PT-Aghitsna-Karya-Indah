{{-- =====================================================================
     Halaman: Laporan Keuangan Proyek (Daftar)
     Tujuan: Menampilkan daftar Laporan Keuangan Proyek yang berdiri sendiri
             (dibuat otomatis saat Rekap Proyek dibuat). Tombol "Detail"
             membuka modal berisi tabel transaksi "Bon" (dengan edit item
             & hapus massal), dan export PDF/Excel tersedia langsung pada
             kolom aksi. Layout toolbar mengikuti pola Invoice Proyek.
     Data dari ProjectFinancialReportController@index:
     - $recaps      : paginator Rekap Proyek (dengan relasi financialReport.items)
     - $categories  : kategori transaksi modul Keuangan Proyek (untuk modal)
     - $rekapOptions: semua Rekap Proyek (untuk dropdown modal tambah)
     Komponen yang di-include:
     - layouts.app
     - x-pagination
     - components.report.project-financial-report.table
     - components.report.project-financial-report.add-modal (global)
     - components.report.project-financial-report.detail-modal (per rekap)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Laporan Keuangan Proyek')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Laporan Keuangan Proyek</h1>

        {{-- Pencarian & Tombol Aksi (layout mengikuti Invoice Proyek) --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('project-financial-report.index') }}" id="filterForm"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" class="min-[1530px]:!w-40" />
                <x-filters.search-input :value="request('search')" placeholder="Cari nama proyek atau lokasi..." responsive="custom" />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Transaksi" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Tabel ==================== --}}
        <x-report.project-financial-report.table :recaps="$recaps" />
    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$recaps" />

    {{-- ==================== Modal Tambah Transaksi ==================== --}}
    @include('components.report.project-financial-report.add-modal', [
        'rekapOptions' => $rekapOptions,
        'categories' => $categories,
    ])

    {{-- ==================== Modals Detail & Edit per Rekap ==================== --}}
    @foreach ($recaps as $recap)
        @include('components.report.project-financial-report.detail-modal', [
            'recap' => $recap,
            'categories' => $categories,
        ])
        @include('components.finance.project-recaps.edit-modal', ['recap' => $recap])
    @endforeach

    {{-- ==================== Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus rekap proyek dan laporan keuangannya yang dipilih?
    </x-modal>

    {{-- ==================== JavaScript ==================== --}}
    @vite(['resources/js/pages/report/project-financial-report/index.js'])
@endsection
