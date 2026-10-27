{{--
    Modal konfirmasi hapus SATU data (dipicu dari menu Aksi ⋮ tiap baris).

    Pemicu: elemen dengan data-delete-row="{id}", (opsional)
    data-delete-row-label="{teks}" dan (opsional) data-delete-row-action="{url}"
    → fi-delete-row.js mengisi id ke form, menampilkan label di modal, lalu
    membuka modal #deleteRowModal. Konfirmasi → submit #deleteRowForm (DELETE)
    berisi satu id, ke `action` atau ke data-delete-row-action milik pemicu
    (untuk route hapus per data, mis. route('stock-in.destroy', $id)).

    Props:
    - action  : URL route hapus (mis. route('items.destroySelected')); boleh
                kosong bila tiap pemicu memberi data-delete-row-action
    - field   : nama field array id yang dibaca controller (default selected_items)
    - heading : judul modal
    - warning : kalimat tambahan (opsional), mis. data turunan yang ikut terhapus
--}}
@props([
    'action' => null,
    'field' => 'selected_items',
    'heading' => 'Hapus Data',
    'warning' => null,
])

<x-fi.modal id="deleteRowModal" :heading="$heading" icon="trash" color="danger" width="md"
    onConfirm="submitDeleteRowForm()" submitLabel="Ya, Hapus">
    Apakah kamu yakin ingin menghapus <span id="delete-row-label" class="font-semibold text-gray-950"></span>?
    @if ($warning)
        {{ $warning }}
    @endif
    Data yang dihapus tidak dapat dikembalikan.

    {{-- Nama input memakai [0] (bukan []) agar tidak ikut tertangkap selector
         input[name="selected_items[]"] milik checkbox tabel (select all &
         status tombol hapus massal). PHP tetap membacanya sebagai array. --}}
    <form id="deleteRowForm" method="POST" @if ($action) action="{{ $action }}" @endif class="hidden">
        @csrf
        @method('DELETE')
        <input type="hidden" id="delete-row-value" name="{{ $field }}[0]">
    </form>
</x-fi.modal>
