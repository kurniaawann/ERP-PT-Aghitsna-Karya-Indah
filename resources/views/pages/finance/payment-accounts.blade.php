{{-- =====================================================================
     Halaman: Rekening Pembayaran (Payment Accounts)
     Tujuan: Mengelola daftar rekening pembayaran (bank/account) yang
             dipakai pada form invoice dan dokumen finance lain, lengkap
             dengan pencarian, tambah, edit, toggle aktif, dan hapus massal
             dengan guard "minimal 1 rekening harus tetap ada".
     Tampilan memakai komponen bergaya Filament (resources/views/components/fi).
     Data dari PaymentAccountController@index:
     - $accounts: Paginator PaymentAccount (15/halaman) hasil
                  PaymentAccountService::buildFilteredQuery($request),
                  difilter oleh request('search'), diurutkan by id.
     - session('usage_error'): pesan error saat rekening yang masih
                  dipakai tabel lain gagal dihapus (AJAX/non-AJAX).
     Komponen yang di-include:
     - components.finance.payment-accounts.table     : toolbar (hapus massal +
                                                       pencarian), tabel, paginasi
     - components.finance.payment-accounts.add-modal / edit-modal : modal CRUD
     - x-fi.modal (deleteModal & errorModal)         : konfirmasi hapus & error
     - x-fi.delete-row-modal                         : hapus 1 rekening dari menu Aksi
     JS: @vite('resources/js/pages/finance/payment-accounts/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekening Pembayaran')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Tombol Tambah --}}
        <x-fi.page-header heading="Rekening Pembayaran"
            :breadcrumbs="['Finance' => null, 'Rekening Pembayaran' => route('payment-accounts.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Rekening</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel Rekening --}}
        {{-- Tabel menampilkan daftar rekening dengan toggle status aktif & menu
             Aksi ⋮ per baris, serta checkbox untuk seleksi hapus massal. --}}
        @include('components.finance.payment-accounts.table')
    </div>

    {{-- SECTION: Modal Tambah --}}
    @include('components.finance.payment-accounts.add-modal')

    {{-- SECTION: Modal Edit (per baris) --}}
    {{-- Satu modal edit dirender untuk setiap rekening agar form tiap
         baris terpisah dan data tidak tercampur. --}}
    @foreach ($accounts as $account)
        @include('components.finance.payment-accounts.edit-modal', ['account' => $account])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Bulk Delete --}}
    {{-- Konfirmasi → submitDeleteForm() (index.js) mengirim AJAX ke
         route('payment-accounts.destroySelected'). --}}
    <x-fi.modal id="deleteModal" heading="Hapus Rekening Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus rekening yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Rekening (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('payment-accounts.destroySelected') yang sama
         dengan hapus massal, jadi guard "minimal 1 rekening" & "masih dipakai"
         tetap berlaku (pesan tampil lewat toast / modal error). --}}
    <x-fi.delete-row-modal :action="route('payment-accounts.destroySelected')" field="selected_accounts"
        heading="Hapus Rekening" />

    {{-- SECTION: Modal Error Penggunaan --}}
    {{-- Ditampilkan oleh JS ketika hapus ditolak karena rekening masih
         dipakai di tabel lain; pesan disuntikkan ke #errorMessage. --}}
    <x-fi.modal id="errorModal" heading="Tidak Dapat Menghapus" icon="exclamation-triangle" color="danger" width="md">
        <p class="text-sm leading-relaxed text-gray-700" id="errorMessage"></p>
    </x-fi.modal>

    {{-- SECTION: Session Data untuk JavaScript --}}
    {{-- Saat controller mengembalikan session('usage_error') (mis. dari
         destroySelected non-AJAX), pesan dibungkus ke elemen tersembunyi
         agar bisa dibaca & ditampilkan oleh JS di modal errorModal. --}}
    @if (session('usage_error'))
        <div id="usageErrorData" data-message="{{ session('usage_error') }}" class="hidden"></div>
    @endif

    {{-- SECTION: JavaScript --}}
    @push('scripts')
        @vite('resources/js/pages/finance/payment-accounts/index.js')
    @endpush
@endsection
