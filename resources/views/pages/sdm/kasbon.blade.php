{{-- ═══════════════════════════════════════════════════════════════════════
     Halaman Index Kasbon
     Halaman utama untuk mengelola kasbon.
     Menampilkan daftar kasbon yang sudah difilter/dicari dengan fitur tambah, edit, dan hapus massal.
     ═══════════════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Kasbon')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Kasbon</h1>

        {{-- Pencarian, Filter & Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Filter & Pencarian --}}
            <form method="GET" action="{{ route('kasbon.index') }}" id="filterForm"
                class="w-full min-[1600px]:w-auto min-[1600px]:flex-1 flex flex-col min-[1600px]:flex-row gap-3">

                <x-filters.month-filter :value="request('month')" responsive="custom" fill onchange="document.getElementById('filterForm').submit()" />
                <x-filters.year-filter :value="request('year')" responsive="custom" fill onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Status --}}
                <select name="status" onchange="document.getElementById('filterForm').submit()"
                    class="border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Belum Dipotong</option>
                    <option value="deducted" {{ request('status') == 'deducted' ? 'selected' : '' }}>Sudah Dipotong</option>
                </select>

                {{-- Filter Status Pembayaran --}}
                <select name="payment_status" onchange="document.getElementById('filterForm').submit()"
                    class="border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Semua Pembayaran</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Cicilan Berjalan</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                </select>

                {{-- Filter Jenis --}}
                <select name="type" onchange="document.getElementById('filterForm').submit()"
                    class="border border-border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    <option value="">Semua Jenis</option>
                    <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>Per Orang</option>
                    <option value="team" {{ request('type') == 'team' ? 'selected' : '' }}>Per Tim</option>
                </select>

                <x-filters.search-input :value="request('search')" placeholder="Cari kasbon..." responsive="custom" />

                {{-- Tombol Reset Filter --}}
                @if (request()->hasAny(['search', 'month', 'year', 'status', 'type', 'payment_status']))
                    <a href="{{ route('kasbon.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <i class="fa-solid fa-rotate-left mr-2"></i>
                        Reset
                    </a>
                @endif
            </form>

            {{-- Tombol Aksi --}}
            <div class="flex items-center gap-2 mt-2 min-[1600px]:mt-0 w-full min-[1600px]:w-auto">
                <div class="flex flex-col min-[1600px]:flex-row gap-2 w-full min-[1600px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Kasbon" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- Tabel Data --}}
        @include('components.sdm.kasbon.table', ['kasbons' => $kasbons])
    </div>

    {{-- Paginasi --}}
    <x-pagination :paginator="$kasbons" />

    {{-- Modal Tambah Kasbon --}}
    @include('components.sdm.kasbon.add-modal', ['employees' => $employees])

    {{-- Modal Edit Kasbon (satu per baris) --}}
    @foreach ($kasbons as $kasbon)
        @include('components.sdm.kasbon.edit-modal', [
            'kasbon' => $kasbon,
            'employees' => $employees,
            'divisions' => $divisions,
        ])
    @endforeach

    {{-- Modal Bayar Cicilan --}}
    @include('components.sdm.kasbon.pay-modal')

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data kasbon yang dipilih? <br>
        <span class="text-error text-sm">Catatan: Kasbon yang sudah dipotong tidak bisa dihapus.</span>
    </x-modal>

    {{-- Kontainer halaman dengan atribut data untuk modul JavaScript --}}
    <div id="kasbon-page"
        data-url-get-weeks="{{ route('payroll.get-weeks') }}"
        class="hidden">
    </div>

    {{-- Modul JavaScript (dimuat melalui Vite) --}}
    @vite('resources/js/pages/sdm/kasbon/index.js')
@endsection
