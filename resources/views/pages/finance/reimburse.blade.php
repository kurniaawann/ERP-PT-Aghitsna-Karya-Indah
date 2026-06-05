@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Reimbursement')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Reimbursement</h1>

        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('reimburse.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek atau kode reimburse..." />

                {{-- Filter Status --}}
                <select name="status" onchange="this.form.submit()"
                    class="border rounded p-2 text-text-primary focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    {{-- Print Dropdown --}}
                    <x-buttons.print-dropdown :excelRoute="route('reimburse.export.excel')" :pdfRoute="route('reimburse.export.pdf')" :queryParams="['search' => request('search'), 'status' => request('status')]" />

                    @if (Auth::user()->role === 'admin')
                        {{-- Admin can add new reimburse --}}
                        <x-buttons.add-button modalId="addModal" text="Tambah Reimburse" />
                    @endif

                    @if (Auth::user()->role === 'superadmin')
                        {{-- Super Admin can approve/reject --}}
                        <button type="button" id="approve-button" disabled onclick="openModal('approveModal')"
                            class="bg-green-600 text-white px-3 py-2 rounded-lg text-sm transition-colors duration-200 flex items-center justify-center gap-2 opacity-50 cursor-not-allowed">
                            <i class="fa-solid fa-check"></i>
                            Setujui
                        </button>

                        <button type="button" id="reject-button" disabled onclick="openModal('rejectModal')"
                            class="bg-red-600 text-white px-3 py-2 rounded-lg text-sm transition-colors duration-200 flex items-center justify-center gap-2 opacity-50 cursor-not-allowed">
                            <i class="fa-solid fa-times"></i>
                            Tolak
                        </button>
                    @endif

                    <x-buttons.delete-button modalId="deleteModal" />
                </div>
            </div>
        </div>

        {{-- Total Info untuk Super Admin --}}
        @if (Auth::user()->role === 'superadmin')
            <div id="selected-info" class="hidden mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-text-primary">
                    <span id="selected-count">0</span> reimburse terpilih | Total: <strong id="selected-total">Rp 0</strong>
                </p>
            </div>
        @endif

        {{-- Table Component --}}
        @include('components.finance.reimburse.table', ['reimburses' => $reimburses])

        {{-- Pagination --}}
        <x-pagination :paginator="$reimburses" />
    </div>

    {{-- Modal Tambah (Admin only) --}}
    @if (Auth::user()->role === 'admin')
        @include('components.finance.reimburse.add-modal')
    @endif

    {{-- Modal Edit untuk setiap reimburse (only for draft) --}}
    @foreach ($reimburses as $reimburse)
        @if ($reimburse->status === 'draft' && Auth::user()->role === 'admin')
            @include('components.finance.reimburse.edit-modal', ['reimburse' => $reimburse])
        @endif
    @endforeach

    {{-- Modal Konfirmasi Approve (Super Admin) --}}
    @if (Auth::user()->role === 'superadmin')
        @include('components.finance.reimburse.approve-modal')
        @include('components.finance.reimburse.reject-modal')
    @endif

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah Anda yakin ingin menghapus reimburse yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @include('partials.finance.reimburse-scripts')
    @include('partials.shared.print-dropdown-script')
@endsection
