{{-- =====================================================================
     Halaman: RAB (Rancangan Anggaran Biaya)
     Tujuan: Menampilkan daftar RAB dengan pencarian, paginasi,
             tambah/detail/edit lewat modal, dan hapus massal.

     Data dari RABController@index:
     - $rabs            : koleksi RAB (paginate 15/halaman, eager-load
                          relasi categories & miscellaneousCosts, hanya
                          data milik user yang login, urut sequence_number
                          desc; pencarian berdasarkan rab_number, recipient)
     - $paymentAccounts : daftar rekening pembayaran aktif (opsi form modal,
                          dari PaymentAccountService::getActiveAccounts)
     - $search          : keyword pencarian

     Komponen: table, add-modal, detail-modal & edit-modal (per RAB),
               deleteModal
     JS: @vite('resources/js/pages/administrasi/rab/index.js')
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - RAB')

@push('scripts')
    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular) untuk halaman index RAB.
         @push('scripts') di bagian atas karena script tambahan (nomor
         otomatis, modal logic) dibutuhkan sejak awal render halaman.
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Halaman index RAB --}}
    @vite('resources/js/pages/administrasi/rab/index.js')
@endpush

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ═══════════════════════════════════════════════════════════
             HEADER: Container utama dengan background surface
             ═══════════════════════════════════════════════════════════ --}}

        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rancangan Anggaran Biaya (RAB)</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Search --}}
            <form method="GET" action="{{ route('rab.index') }}" class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nomor RAB / penerima..." />
            </form>

            {{-- Tombol Aksi --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addRABModal" text="Tambah RAB" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Daftar RAB
             ═══════════════════════════════════════════════════════════ --}}
        {{-- Tabel RAB --}}
        @include('components.administrasi.RAB.table', ['rabs' => $rabs])

    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PAGINATION: Navigasi halaman data
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$rabs" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah RAB baru
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Modal Tambah --}}
    @include('components.administrasi.RAB.add-modal', ['paymentAccounts' => $paymentAccounts])

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL DETAIL & EDIT: Satu modal per RAB.
         Alur: iterasi setiap $rab pada halaman aktif, lalu render
         pasangan modal detail (read-only) dan modal edit. Kedua modal
         membutuhkan $paymentAccounts sebagai opsi rekening pembayaran.
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Modal Detail & Edit (per RAB) --}}
    @foreach ($rabs as $rab)
        @include('components.administrasi.RAB.detail-modal', [
            'rab' => $rab,
            'paymentAccounts' => $paymentAccounts,
        ])
        @include('components.administrasi.RAB.edit-modal', [
            'rab' => $rab,
            'paymentAccounts' => $paymentAccounts,
        ])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Modal Konfirmasi Hapus --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus RAB yang dipilih?
    </x-modal>
@endsection
