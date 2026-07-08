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
                {{-- Filter Status --}}
                <div class="w-full lg:w-auto">
                    <label for="status-select" class="sr-only">Semua Status</label>
                    <select name="status" id="status-select" onchange="this.form.requestSubmit()"
                        class="block w-full lg:w-40 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                {{-- Filter Bulan --}}
                <x-filters.month-filter name="month" :value="request('month')" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter name="year" :value="request('year')" />

                {{-- Search --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek atau kode reimburse..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    {{-- Print Dropdown --}}
                    <x-buttons.print-dropdown :excelRoute="route('reimburse.export.excel')" :pdfRoute="route('reimburse.export.pdf')" :queryParams="['search' => request('search'), 'status' => request('status'), 'month' => request('month'), 'year' => request('year')]" />

                    @if (Auth::user()->role === 'admin')
                        {{-- Admin can add new reimburse --}}
                        <x-buttons.add-button modalId="addModal" text="Tambah Reimburse" />
                    @endif

                    @if (Auth::user()->role === 'superadmin')
                        {{-- Super Admin Approval Dropdown --}}
                        <div class="relative inline-block text-left w-full sm:w-auto">
                            <button type="button" id="approval-dropdown-button" disabled
                                class="w-full sm:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium opacity-50 cursor-not-allowed">
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Aksi Persetujuan</span>
                                <i class="fa-solid fa-chevron-down text-xs ml-auto sm:ml-0"></i>
                            </button>

                            <div id="approval-dropdown-menu"
                                class="hidden absolute left-0 sm:right-0 sm:left-auto mt-2 w-full sm:w-48 rounded-lg shadow-lg bg-surface-base border border-border-strong z-50">
                                <div class="py-1" role="menu">
                                    <button type="button" onclick="openModal('approveModal')"
                                        class="flex items-center gap-3 w-full px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                                        <i class="fa-solid fa-check text-success w-4"></i>
                                        <span>Setujui</span>
                                    </button>
                                    <button type="button" onclick="openModal('rejectModal')"
                                        class="flex items-center gap-3 w-full px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                                        <i class="fa-solid fa-times text-error w-4"></i>
                                        <span>Tolak</span>
                                    </button>
                                </div>
                            </div>
                        </div>
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

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$reimburses" />

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
