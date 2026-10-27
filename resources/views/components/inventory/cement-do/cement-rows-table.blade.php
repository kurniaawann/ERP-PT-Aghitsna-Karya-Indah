{{-- Repeater "Data Semen" untuk modal tambah/edit DO Semen.
     Pakai: <x-inventory.cement-do.cement-rows-table mode="add|edit"> baris awal </...>
     - mode="add"  : tombol #add-row-btn, tbody #cement-rows
     - mode="edit" : tombol .add-row-btn, tbody .cement-rows
     (selector di atas dipakai resources/js/pages/inventory/cement-do/index.js) --}}
@props(['mode' => 'add'])

{{-- PENTING: class "mb-3" pada pembungkus ini dipakai JS
     (btn.closest('.mb-3').querySelector('.cement-rows')) — jangan dihapus
     dan jangan pakai "mb-3" di elemen antara tombol & pembungkus ini. --}}
<div class="mb-3 grid gap-y-2">
    <div class="flex items-center justify-between gap-3">
        <span class="text-sm font-medium leading-6 text-gray-950">
            Data Semen<sup class="ms-0.5 font-medium text-red-600">*</sup>
        </span>

        @if ($mode === 'add')
            <x-fi.button id="add-row-btn" color="gray" size="sm" icon="plus">Tambah Baris</x-fi.button>
        @else
            <x-fi.button color="gray" size="sm" icon="plus" class="add-row-btn">Tambah Baris</x-fi.button>
        @endif
    </div>

    <div class="overflow-x-auto rounded-xl shadow-sm ring-1 ring-gray-950/5">
        <table class="w-full table-auto divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="min-w-[9.5rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Tanggal</th>
                    <th class="min-w-[12rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Nama Proyek</th>
                    <th class="min-w-[10rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Nama</th>
                    <th class="min-w-[6.5rem] px-2 py-2 text-center text-sm font-semibold text-gray-950">Jumlah (zak)</th>
                    <th class="min-w-[9rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">Harga</th>
                    <th class="min-w-[9.5rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Tgl Lunas</th>
                    <th class="w-12 px-2 py-2"><span class="sr-only">Hapus</span></th>
                </tr>
            </thead>
            <tbody @if ($mode === 'add') id="cement-rows" @endif
                class="{{ $mode === 'add' ? '' : 'cement-rows ' }}fi-repeater divide-y divide-gray-200 bg-white">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    <p class="text-sm text-gray-500">
        Satu DO dapat memuat banyak baris data semen. Klik “Tambah Baris” untuk menambahkan.
    </p>
</div>
