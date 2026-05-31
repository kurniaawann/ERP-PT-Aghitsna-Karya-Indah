@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - RAB')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rancangan Anggaran Biaya (RAB)</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('rab.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nomor RAB / penerima..." />
            </form>

            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addRABModal" text="Tambah RAB" />
                </div>
            </div>
        </div>

        {{-- Table --}}
        @include('components.administrasi.rab.table', ['rabs' => $rabs])

        {{-- Pagination --}}
        <x-pagination :paginator="$rabs" />
    </div>

    {{-- Add Modal --}}
    @include('components.administrasi.rab.add-modal', ['paymentAccounts' => $paymentAccounts])

    {{-- Detail & Edit Modals --}}
    @foreach ($rabs as $rab)
        @include('components.administrasi.rab.detail-modal', [
            'rab' => $rab,
            'paymentAccounts' => $paymentAccounts,
        ])
        @include('components.administrasi.rab.edit-modal', [
            'rab' => $rab,
            'paymentAccounts' => $paymentAccounts,
        ])
    @endforeach

    {{-- Delete Confirm Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus RAB yang dipilih?
    </x-modal>

    {{-- Scripts --}}
    @include('partials.administrasi.rab-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
