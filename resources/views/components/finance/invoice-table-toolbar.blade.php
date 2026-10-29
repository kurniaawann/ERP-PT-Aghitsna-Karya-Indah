{{-- Toolbar tabel Invoice (Barang | Alumunium | Proyek), gaya Filament —
     sama seperti halaman lain (Barang Masuk, Pengembalian Barang, ...).
     Dipakai di dalam x-fi.card ber-Alpine `selected` (jumlah invoice dicentang).

     Kiri : aksi massal, hanya tampil saat ada invoice yang dicentang; status
            disabled #delete-button tetap diatur JS halaman.
     Kanan: form GET filter bulan / tahun / status + pencarian. Bulan/tahun/
            status langsung submit saat diganti; pencarian saat Enter.

     Props:
     - action            : URL route index (tujuan filter & tombol Reset)
     - searchPlaceholder : placeholder pencarian --}}
@props(['action', 'searchPlaceholder' => 'Cari invoice...'])

@php
    $isFiltered = request()->filled('search') || request()->filled('month') || request()->filled('year') || request()->filled('status');
@endphp

<div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
    {{-- Aksi massal: hanya tampil saat ada invoice yang dicentang, agar tidak
         tertukar dengan hapus satu invoice lewat menu ⋮ --}}
    <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
        <span class="whitespace-nowrap text-sm text-gray-500">
            <span class="font-semibold text-gray-950" x-text="selected"></span> invoice dipilih
        </span>

        <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
            onclick="openModal('deleteModal')">
            Hapus yang dipilih
        </x-fi.button>
    </div>

    {{-- Filter & Pencarian (kanan) --}}
    <form method="GET" action="{{ $action }}" id="filterForm"
        class="grid w-full grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:justify-end xl:ms-auto xl:w-auto">
        <div class="sm:w-44">
            <x-fi.month-select :value="request('month')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="sm:w-36">
            <x-fi.year-select :value="request('year')" onchange="this.form.requestSubmit()" />
        </div>
        <div class="col-span-2 sm:w-40">
            <x-fi.select name="status" id="status-select" aria-label="Filter Status" :value="request('status')"
                :options="['' => 'Semua Status', 'lunas' => 'Sudah Lunas', 'belum' => 'Belum Lunas']"
                onchange="this.form.requestSubmit()" data-fi-select />
        </div>
        <x-fi.search-input :value="request('search')" :placeholder="$searchPlaceholder" class="col-span-2 sm:w-64" />

        @if ($isFiltered)
            <x-fi.link :href="$action" color="gray" icon="x-mark"
                class="col-span-2 justify-self-start whitespace-nowrap" title="Hapus semua filter & pencarian">
                Reset
            </x-fi.link>
        @endif
    </form>
</div>
