@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Invoice Barang')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Invoice Barang</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('item-invoice.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari invoice, penerima, atau keterangan..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Invoice" responsive="custom" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="uppercase tracking-wide text-text-secondary">Total Invoice</p>
                <p class="text-xl font-bold text-text-primary mt-1">Rp
                    {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="uppercase tracking-wide text-text-secondary">Jumlah Invoice</p>
                <p class="text-xl font-bold text-success mt-1">{{ $totals->invoice_count ?? 0 }} invoice</p>
            </div>
            <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
                <p class="uppercase tracking-wide text-text-secondary">Lunas</p>
                <p class="text-xl font-bold text-success mt-1">{{ $totals->paid_count ?? 0 }} invoice</p>
            </div>
        </div>

        <x-finance.product-invoices.table :invoices="$invoices" />
    </div>

    <x-pagination :paginator="$invoices" />

    <x-finance.product-invoices.add-modal :items="$items" />

    @foreach ($invoices as $invoice)
        <x-finance.product-invoices.edit-modal :invoice="$invoice" :items="$items" />
        <x-finance.product-invoices.detail-modal :invoice="$invoice" />
    @endforeach

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    @include('partials.shared.print-dropdown-script')

    <script>
        window._itemsData = {!! json_encode($items->map(fn($item) => [
            'id_item' => $item->id_item,
            'name_item' => $item->name_item,
            'capital_price' => $item->capital_price,
            'selling_price' => $item->selling_price,
            'quantity' => $item->quantity,
        ])->values()) !!};
    </script>
@endsection

@push('scripts')
    @vite(['resources/js/pages/finance/product-invoices/index.js'])
@endpush
