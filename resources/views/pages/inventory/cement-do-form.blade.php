{{-- =====================================================================
     Halaman: Form Tambah / Edit DO Semen (Inventory)
     Tujuan: Pengganti modal tambah/edit DO Semen — informasi DO, ringkasan
             (total & estimasi profit), dan repeater baris Data Semen dalam
             satu halaman penuh agar tabel baris semen lega.

     Data dari CementDeliveryOrderController@create / @edit:
     - $cementDeliveryOrder : CementDeliveryOrder (edit, beserta cements) |
                 null (tambah)

     Submit ke route('cement-do.store') (POST) / route('cement-do.update')
     (PUT); berhasil → kembali ke daftar DO, gagal validasi → kembali ke
     halaman ini dengan old() (baris semen ikut dipulihkan).

     JS yang di-load:
     - @vite('resources/js/pages/inventory/cement-do/form.js')
     ===================================================================== --}}
@extends('layouts.app')

@php
    $isEdit = (bool) $cementDeliveryOrder;

    // Baris Data Semen awal: input lama (validasi gagal) → data tersimpan → 1 baris kosong
    $oldRows = session()->hasOldInput() ? array_values((array) old('cements', [])) : null;
@endphp

@section('title', 'PT Aghitsna Karya Indah - ' . ($isEdit ? 'Edit' : 'Tambah') . ' DO Semen')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header :heading="$isEdit ? 'Edit DO Semen' : 'Tambah DO Semen'"
            :breadcrumbs="['Inventory' => null, 'DO Semen' => route('cement-do.index'), ($isEdit ? 'Edit' : 'Tambah') => null]" />

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        <form id="cement-do-form" method="POST"
            action="{{ $isEdit ? route('cement-do.update', $cementDeliveryOrder->no) : route('cement-do.store') }}"
            class="flex flex-col gap-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- SECTION: Informasi DO --}}
                <x-fi.section heading="Informasi DO" class="lg:col-span-2">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">

                        {{-- Field: No (dibuat otomatis, tidak dapat diubah) --}}
                        <x-fi.field label="No. DO" :hint="$isEdit ? 'No. DO tidak dapat diubah.' : 'Dibuat otomatis mengikuti bulan tanggal DO.'">
                            <x-fi.input :value="$cementDeliveryOrder?->no" placeholder="Otomatis" prefix-icon="lock-closed"
                                aria-label="No. DO" readonly />
                        </x-fi.field>

                        {{-- Field: Tanggal DO --}}
                        <x-fi.field label="Tanggal DO" for="tanggal" required>
                            <x-fi.input type="date" id="tanggal" name="tanggal" required
                                :value="old('tanggal', $cementDeliveryOrder?->tanggal?->format('Y-m-d') ?? date('Y-m-d'))"
                                oninvalid="this.setCustomValidity('Tanggal DO tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                        </x-fi.field>

                        {{-- Field: Tanggal Datang --}}
                        <x-fi.field label="Tanggal Datang" for="tanggal-datang">
                            <x-fi.input type="date" id="tanggal-datang" name="tanggal_datang"
                                :value="old('tanggal_datang', $cementDeliveryOrder?->tanggal_datang?->format('Y-m-d'))" />
                        </x-fi.field>

                        {{-- Field: Tanggal Bayar --}}
                        <x-fi.field label="Tanggal Bayar" for="tanggal-bayar">
                            <x-fi.input type="date" id="tanggal-bayar" name="tanggal_bayar"
                                :value="old('tanggal_bayar', $cementDeliveryOrder?->tanggal_bayar?->format('Y-m-d'))" />
                        </x-fi.field>

                        {{-- Field: Harga Modal (per DO) --}}
                        <x-fi.field label="Harga Modal" for="harga-modal" required class="sm:col-span-2"
                            hint="Total modal untuk seluruh baris semen dalam DO ini.">
                            <x-fi.input id="harga-modal" name="harga_modal" placeholder="Rp 0" prefix-icon="banknotes"
                                :value="old('harga_modal', 'Rp ' . number_format($cementDeliveryOrder?->harga_modal ?? 0, 0, ',', '.'))"
                                required inputmode="numeric"
                                oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                        </x-fi.field>
                    </div>
                </x-fi.section>

                {{-- SECTION: Ringkasan (dihitung ulang form.js setiap input berubah).
                     Di layar kecil ditaruh setelah Data Semen. --}}
                <x-fi.section heading="Ringkasan" class="order-last self-start lg:order-none">
                    <dl class="grid gap-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Jumlah baris</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="rows">0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Total volume</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="volume">0 zak</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Subtotal penjualan</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="subtotal">Rp 0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">Harga modal</dt>
                            <dd class="font-medium tabular-nums text-gray-950" data-summary="modal">Rp 0</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-3">
                            <dt class="font-semibold text-gray-950">Estimasi profit</dt>
                            <dd class="text-base font-semibold tabular-nums text-emerald-600" data-summary="profit">Rp 0</dd>
                        </div>
                    </dl>

                    <p id="profit-warning" class="mt-3 hidden text-sm text-red-600">
                        <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                        Harga modal melebihi subtotal penjualan.
                    </p>
                </x-fi.section>

                {{-- SECTION: Data Semen (repeater baris) --}}
                <x-fi.section heading="Data Semen" description="Satu DO dapat memuat banyak baris data semen."
                    flush class="lg:col-span-3">
                    <x-slot:actions>
                        <x-fi.button id="add-row-btn" color="gray" size="sm" icon="plus" data-add-cement-row>
                            Tambah Baris
                        </x-fi.button>
                    </x-slot:actions>

                    <x-inventory.cement-do.cement-rows-table>
                        @if (is_array($oldRows))
                            @foreach ($oldRows as $old)
                                @include('components.inventory.cement-do.cement-row', ['index' => $loop->index, 'old' => $old])
                            @endforeach
                        @elseif ($isEdit && $cementDeliveryOrder->cements->isNotEmpty())
                            @foreach ($cementDeliveryOrder->cements as $cement)
                                @include('components.inventory.cement-do.cement-row', ['index' => $loop->index, 'cement' => $cement])
                            @endforeach
                        @else
                            @include('components.inventory.cement-do.cement-row', ['index' => 0, 'cement' => null])
                        @endif
                    </x-inventory.cement-do.cement-rows-table>
                </x-fi.section>
            </div>

            {{-- SECTION: Aksi Form (kanan bawah: [Batal] [Simpan]) --}}
            <div class="flex flex-wrap items-center justify-end gap-3">
                <x-fi.button color="gray" :href="route('cement-do.index')">Batal</x-fi.button>
                <x-fi.button type="submit">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </x-fi.button>
            </div>
        </form>
    </div>

    {{-- Template baris Data Semen baru ("IDX" diganti indeks unik oleh form.js) --}}
    <template id="cement-row-template">
        @include('components.inventory.cement-do.cement-row', ['index' => 'IDX', 'cement' => null])
    </template>

    {{-- SECTION: Scripts (JavaScript Modular) --}}
    @push('scripts')
        @vite('resources/js/pages/inventory/cement-do/form.js')
    @endpush
@endsection
