{{-- Tabel repeater "Data Semen" pada halaman form DO Semen (tambah/edit).
     Pakai: <x-inventory.cement-do.cement-rows-table> baris awal </...>
     Hook JS (resources/js/pages/inventory/cement-do/form.js):
     - tbody#cement-rows        : tempat baris .cement-row
     - #cement-rows-empty       : pesan kosong (tampil bila tidak ada baris)
     - [data-add-cement-row]    : tombol "Tambah Baris"
     - [data-summary="volume"] / [data-summary="subtotal"] : total di <tfoot> --}}
<div class="overflow-x-auto">
    <table class="w-full table-auto divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="min-w-[9.5rem] py-2 pe-2 ps-4 text-start text-sm font-semibold text-gray-950">Tanggal</th>
                <th class="min-w-[12rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">
                    Nama Proyek<sup class="ms-0.5 font-medium text-red-600">*</sup>
                </th>
                <th class="min-w-[10rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Nama</th>
                <th class="min-w-[6.5rem] px-2 py-2 text-center text-sm font-semibold text-gray-950">
                    Jumlah (zak)<sup class="ms-0.5 font-medium text-red-600">*</sup>
                </th>
                <th class="min-w-[9rem] px-2 py-2 text-end text-sm font-semibold text-gray-950">
                    Harga / zak<sup class="ms-0.5 font-medium text-red-600">*</sup>
                </th>
                <th class="min-w-[9.5rem] px-2 py-2 text-start text-sm font-semibold text-gray-950">Tgl Lunas</th>
                <th class="min-w-[8rem] py-2 pe-2 ps-4 text-end text-sm font-semibold text-gray-950">Total</th>
                <th class="w-12 py-2 pe-4 ps-2"><span class="sr-only">Hapus</span></th>
            </tr>
        </thead>

        <tbody id="cement-rows" class="fi-repeater divide-y divide-gray-200 bg-white">
            {{ $slot }}
        </tbody>

        <tfoot class="border-t border-gray-200 bg-gray-50">
            <tr>
                <td colspan="3" class="py-3 pe-2 ps-4 text-sm font-semibold text-gray-950">Total</td>
                <td class="px-2 py-3 text-center text-sm font-semibold tabular-nums text-gray-950" data-summary="volume">0 zak</td>
                <td colspan="2"></td>
                <td class="whitespace-nowrap py-3 pe-2 ps-4 text-end text-sm font-semibold tabular-nums text-gray-950" data-summary="subtotal">Rp 0</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<div id="cement-rows-empty" class="hidden border-t border-gray-200">
    <x-fi.empty-state icon="truck" heading="Belum ada data semen"
        description="DO tetap bisa disimpan tanpa data semen dan dilengkapi nanti.">
        <x-fi.button color="gray" icon="plus" data-add-cement-row>Tambah Baris</x-fi.button>
    </x-fi.empty-state>
</div>
