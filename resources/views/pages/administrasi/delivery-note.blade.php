@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Surat Jalan')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Surat Jalan</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('delivery-note.administrasi.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari surat jalan..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('delivery-note.administrasi.export.pdf')" :queryParams="['search' => request('search')]" />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Surat Jalan" />
                </div>
            </div>
        </div>

        {{-- Table Component --}}
        @include('components.administrasi.delivery-note.table', ['deliveryNotes' => $deliveryNotes])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$deliveryNotes" />

    {{-- Modal Tambah --}}
    @include('components.administrasi.delivery-note.add-modal')

    {{-- Modal Edit untuk setiap delivery note --}}
    @foreach ($deliveryNotes as $deliveryNote)
        @include('components.administrasi.delivery-note.edit-modal', ['deliveryNote' => $deliveryNote])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus surat jalan yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.administrasi.delivery-note-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
