@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekening Pembayaran')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ==================== Header & Toolbar ==================== --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekening Pembayaran</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('payment-accounts.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari rekening..." />
            </form>

            {{-- Tombol Aksi --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <button type="button" id="delete-button" onclick="openModal('deleteModal')" disabled
                        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-btn-delete">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        <span>Hapus</span>
                    </button>

                    <x-buttons.add-button modalId="addModal" text="Tambah Rekening" />
                </div>
            </div>
        </div>

        {{-- ==================== Tabel Rekening ==================== --}}
        @include('components.finance.payment-accounts.table')

    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$accounts" />

    {{-- ==================== Modal Tambah ==================== --}}
    @include('components.finance.payment-accounts.add-modal')

    {{-- ==================== Modal Edit (per baris) ==================== --}}
    @foreach ($accounts as $account)
        @include('components.finance.payment-accounts.edit-modal', ['account' => $account])
    @endforeach

    {{-- ==================== Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus rekening yang dipilih?
    </x-modal>

    {{-- ==================== Modal Error Penggunaan ==================== --}}
    <x-modal id="errorModal" title="Tidak Dapat Menghapus" :readonly="true">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-error-light">
                <i class="fa-solid fa-triangle-exclamation text-error text-lg"></i>
            </div>
            <p class="text-text-primary text-sm leading-relaxed" id="errorMessage"></p>
        </div>
    </x-modal>

    {{-- ==================== Session Data untuk JavaScript ==================== --}}
    @if (session('usage_error'))
        <div id="usageErrorData" data-message="{{ session('usage_error') }}" class="hidden"></div>
    @endif

    {{-- ==================== JavaScript ==================== --}}
    @vite('resources/js/pages/finance/payment-accounts/index.js')
@endsection
