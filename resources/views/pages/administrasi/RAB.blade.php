@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - RAB')

@push('styles')
    {{-- Halaman index RAB --}}
    @vite('resources/js/pages/administration/budgets/index.js')
@endpush

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rancangan Anggaran Biaya (RAB)</h1>

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

        {{-- Tabel RAB --}}
        @include('components.administrasi.RAB.table', ['rabs' => $rabs])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$rabs" />

    {{-- Modal Tambah --}}
    @include('components.administrasi.RAB.add-modal', ['paymentAccounts' => $paymentAccounts])

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

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus RAB yang dipilih?
    </x-modal>

    {{-- Script RAB --}}
    @include('partials.administrasi.rab-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
