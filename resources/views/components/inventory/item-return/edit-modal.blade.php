{{-- Modal Edit Return Barang --}}
@php
    $item = $record;
@endphp

<x-modal id="editModal-{{ $item->id_return }}" title="Edit Return Barang"
    action="{{ route('item-return.update', $item->id_return) }}" method="POST" buttonText="Update">
    @method('PUT')

    {{-- Field: Tipe Return (readonly) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Return</label>
        <input type="text" value="{{ ucfirst($item->return_type) }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-primary" disabled>
    </div>

    {{-- Field: Barang (readonly) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Barang</label>
        <input type="text" value="{{ $item->id_item }} - {{ $item->item->name_item }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-primary" disabled>
    </div>

    {{-- Field: Jumlah --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" id="editQuantity-{{ $item->id_return }}" name="quantity" value="{{ $item->quantity }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            data-max-quantity="{{ $maxQuantity }}" min="1" required>
        <p id="editQuantityWarning-{{ $item->id_return }}" class="text-error text-sm mt-1 hidden">
            <i class="fa-solid fa-circle-exclamation"></i> Jumlah return tidak boleh melebihi stok yang tersedia
        </p>
        <p id="editAvailableStock-{{ $item->id_return }}" class="text-primary text-sm mt-1 text-xs">Stok tersedia:
            {{ $maxQuantity }}</p>
    </div>

    {{-- Field: Alasan Return --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alasan Return</label>
        <input type="text" name="reason" value="{{ $item->reason }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Rusak, Tidak sesuai, dll">
    </div>

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ $item->date->format('Y-m-d') }}"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required>
    </div>

    {{-- Field: Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            rows="3">{{ $item->notes }}</textarea>
    </div>
</x-modal>
