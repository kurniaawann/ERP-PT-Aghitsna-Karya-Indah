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
            <input type="text" value="{{ ucfirst($item->return_type) }}" class="w-full border rounded p-2 bg-gray-100"
                disabled>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Barang</label>
            <input type="text" value="{{ $item->id_item }} - {{ $item->item->name_item }}"
                class="w-full border rounded p-2 bg-gray-100" disabled>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
            <input type="number" id="editQuantity-{{ $item->id_return }}" name="quantity" value="{{ $item->quantity }}"
                class="w-full border rounded p-2" data-max-quantity="{{ $maxQuantity }}" min="1" required>
            <p id="editQuantityWarning-{{ $item->id_return }}" class="text-red-500 text-sm mt-1 hidden">
                <i class="fa-solid fa-circle-exclamation"></i> Jumlah return tidak boleh melebihi stok yang tersedia
            </p>
            <p id="editAvailableStock-{{ $item->id_return }}" class="text-blue-500 text-sm mt-1 text-xs">Stok tersedia:
                {{ $maxQuantity }}</p>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Alasan Return</label>
            <input type="text" name="alasan" value="{{ $item->alasan }}" class="w-full border rounded p-2"
                placeholder="Rusak, Tidak sesuai, dll">
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="tanggal" value="{{ $item->tanggal->format('Y-m-d') }}"
                class="w-full border rounded p-2" required>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Keterangan</label>
            <textarea name="keterangan" class="w-full border rounded p-2" rows="3">{{ $item->keterangan }}</textarea>
        </div>
    </x-modal>
@endforeach
