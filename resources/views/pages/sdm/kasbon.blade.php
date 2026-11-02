{{-- ═══════════════════════════════════════════════════════════════════════
     Halaman: Index Kasbon (kasbon, gaya Filament)
     Tujuan: Halaman utama untuk mengelola kasbon (cash advance): daftar
     kasbon yang difilter/dicari dengan fitur tambah, edit, hapus massal /
     satu data, dan pembayaran cicilan.

     Data dari KasbonController@index:
     - $kasbons   : LengthAwarePaginator daftar kasbon (dengan relasi employee)
     - $employees : Collection seluruh karyawan (dropdown pemilih di modal)
     - $divisions : Collection seluruh divisi (dropdown untuk kasbon tipe Tim)
     - $projects  : daftar proyek (baris proyek kasbon Tim)

     Komponen yang di-include:
     - x-sdm.allowance-tabs (admin)      : tab Lembur | Kasbon
     - components.sdm.kasbon.table       : toolbar + tabel + paginasi
     - components.sdm.kasbon.add-modal   : modal tambah kasbon
     - components.sdm.kasbon.edit-modal  : modal edit per kasbon (loop)
     - components.sdm.kasbon.pay-modal   : modal bayar cicilan
     - x-fi.modal / x-fi.delete-row-modal: konfirmasi hapus massal / satu data

     Alur logika yang perlu diperhatikan:
     - Field di modal menyesuaikan JENIS kasbon: "Per Orang" memilih satu
       karyawan (employee_id), "Per Tim" memilih divisi + proyek (JS modul).
     - Periode payroll di-resolve dari tanggal kasbon (route payroll.get-weeks).
     - Filter bulan/tahun berdasar period_start_date (periode payroll),
       bukan tanggal kasbon.

     JS yang di-load:
     - @vite('resources/js/pages/sdm/kasbon/index.js')
     ═══════════════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Kasbon')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi --}}
        <x-fi.page-header heading="Data Kasbon"
            :breadcrumbs="['Human Resource' => null, 'Tunjangan & Potongan' => null, 'Kasbon' => route('kasbon.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Kasbon</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tab Lembur | Kasbon (admin) --}}
        @if (auth()->user()->isAdmin())
            <x-sdm.allowance-tabs active="kasbon" />
        @endif

        {{-- SECTION: Tabel (+ filter, hapus massal, paginasi) --}}
        @include('components.sdm.kasbon.table', ['kasbons' => $kasbons])
    </div>

    {{-- SECTION: Modal Tambah --}}
    @include('components.sdm.kasbon.add-modal', ['employees' => $employees])

    {{-- SECTION: Modal Edit (satu per kasbon) --}}
    @foreach ($kasbons as $kasbon)
        @include('components.sdm.kasbon.edit-modal', [
            'kasbon' => $kasbon,
            'employees' => $employees,
            'divisions' => $divisions,
        ])
    @endforeach

    {{-- SECTION: Modal Bayar Cicilan --}}
    @include('components.sdm.kasbon.pay-modal')

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data kasbon yang dipilih?
        <span class="mt-2 block text-red-600">Catatan: Kasbon yang masih terhubung payroll tidak bisa dihapus.</span>
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Kasbon (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu kode ke route('kasbon.destroySelected') yang sama dengan hapus massal. --}}
    <x-fi.delete-row-modal :action="route('kasbon.destroySelected')" field="selected_kasbons" heading="Hapus Kasbon" />

    {{-- Kontainer data untuk modul JS (URL daftar minggu untuk resolusi periode) --}}
    <div id="kasbon-page" data-url-get-weeks="{{ route('payroll.get-weeks') }}" class="hidden"></div>

    @push('scripts')
        @vite('resources/js/pages/sdm/kasbon/index.js')
    @endpush
@endsection
