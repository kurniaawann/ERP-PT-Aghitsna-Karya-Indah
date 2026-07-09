{{-- Modal Edit Return Barang --}}
@foreach (isset($record) ? [$record] : [] as $item)
    @php
        // Hitung max quantity yang bisa di-return
        if ($item->return_type === 'masuk') {
            $stockIn = $stockIns->firstWhere('id_stock_in', $item->id_stock_in);
            $totalOtherReturns = \App\Models\Inventory\ItemReturn::where('id_stock_in', $item->id_stock_in)
                ->where('id_return', '!=', $item->id_return)
                ->where('return_type', 'masuk')
                ->sum('quantity');
            $maxQuantity = $stockIn ? $stockIn->quantity - $totalOtherReturns : 0;
        } else {
            $stockOut = $stockOuts->firstWhere('id_stock_out', $item->id_stock_out);
            $totalOtherReturns = \App\Models\Inventory\ItemReturn::where('id_stock_out', $item->id_stock_out)
                ->where('id_return', '!=', $item->id_return)
                ->where('return_type', 'keluar')
                ->sum('quantity');
            $maxQuantity = $stockOut ? $stockOut->quantity - $totalOtherReturns : 0;
        }
    @endphp

    <x-modal id="editModal-{{ $item->id_return }}" title="Edit Return Barang"
        action="{{ route('item-return.update', $item->id_return) }}" method="POST" buttonText="Update">
        @method('PUT')

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tipe Return</label>
            <input type="text" value="{{ ucfirst($item->return_type) }}"
                class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-primary" disabled>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Barang</label>
            <input type="text" value="{{ $item->id_item }} - {{ $item->item->name_item }}"
                class="w-full border border-border-strong rounded p-2 bg-surface-secondary text-text-primary" disabled>
        </div>

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

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Alasan Return</label>
            <input type="text" name="reason" value="{{ $item->reason }}"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Rusak, Tidak sesuai, dll">
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="date" value="{{ $item->date->format('Y-m-d') }}"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Keterangan</label>
            <textarea name="notes" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                rows="3">{{ $item->notes }}</textarea>
        </div>
    </x-modal>
@endforeach
