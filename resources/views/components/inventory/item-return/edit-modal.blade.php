{{-- Modal Edit Pengembalian Barang (gaya Filament). Tipe & barang tidak bisa diubah.
     Hook JS (item-returns/index.js): [id^="editModal-"] form + button[type=submit],
     #editQuantity-{id}[data-max-quantity], #editQuantityWarning-{id},
     #editAvailableStock-{id}.
     Penyimpanan gagal → modal ini dibuka lagi (name="_modal") berisi input
     lama + pesan error. --}}
@php
    $item = $record;
    $modalId = 'editModal-' . $item->id_return;

    // Input lama hanya dipakai bila error berasal dari modal ini
    $isEditError = $errors->any() && old('_modal') === $modalId;
    $oldValue = fn (string $key, $default = null) => $isEditError ? old($key, $default) : $default;
@endphp

<x-fi.modal :id="$modalId" heading="Edit Pengembalian Barang"
    :description="'ID: ' . $item->id_return . ' — tipe & barang tidak dapat diubah.'"
    icon="pencil-square" :action="route('item-return.update', $item->id_return)" method="PUT" submitLabel="Update"
    width="xl">

    <input type="hidden" name="_modal" value="{{ $modalId }}">

    @if ($isEditError)
        <x-fi.validation-errors />
    @endif

    {{-- Tipe & Barang (readonly) --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-fi.field label="Tipe Pengembalian">
            <x-fi.input :value="$item->return_type === 'masuk' ? 'Retur Barang Masuk — ke supplier' : 'Retur Barang Keluar — dari proyek'"
                aria-label="Tipe pengembalian" disabled />
        </x-fi.field>

        <x-fi.field label="Barang">
            <x-fi.input :value="$item->id_item . ' - ' . ($item->item?->name_item ?? '-')" aria-label="Barang" disabled />
        </x-fi.field>
    </div>

    {{-- Jumlah & Tanggal --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-fi.field label="Jumlah" for="editQuantity-{{ $item->id_return }}" required>
            <x-fi.input type="number" id="editQuantity-{{ $item->id_return }}" name="quantity" :value="$oldValue('quantity', $item->quantity)"
                data-max-quantity="{{ $maxQuantity }}" min="1" suffix="unit" required />
            <p id="editQuantityWarning-{{ $item->id_return }}" class="hidden text-sm text-red-600">
                <x-fi.icon name="exclamation-circle" class="-mt-0.5 inline h-4 w-4" />
                Jumlah melebihi batas yang dapat dikembalikan
            </p>
            <p id="editAvailableStock-{{ $item->id_return }}" class="text-sm text-gray-500">
                Maks. dapat dikembalikan: {{ number_format($maxQuantity, 0, ',', '.') }} unit
            </p>
        </x-fi.field>

        <x-fi.field label="Tanggal" for="edit-date-{{ $item->id_return }}" required>
            <x-fi.input type="date" id="edit-date-{{ $item->id_return }}" name="date"
                :value="$oldValue('date', $item->date->format('Y-m-d'))" required />
        </x-fi.field>
    </div>

    {{-- Field: Alasan --}}
    <x-fi.field label="Alasan" for="edit-reason-{{ $item->id_return }}">
        <x-fi.input id="edit-reason-{{ $item->id_return }}" name="reason" :value="$oldValue('reason', $item->reason)"
            placeholder="Mis. rusak, tidak sesuai pesanan" />
    </x-fi.field>

    {{-- Field: Keterangan --}}
    <x-fi.field label="Keterangan" for="edit-notes-{{ $item->id_return }}">
        <x-fi.textarea id="edit-notes-{{ $item->id_return }}" name="notes" rows="3"
            placeholder="Masukkan keterangan (opsional)">{{ $oldValue('notes', $item->notes) }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
