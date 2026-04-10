{{-- Modal Tambah Return Barang --}}
<x-modal id="addModal" title="Tambah Return Barang" action="{{ route('item-return.store') }}" method="POST"
    buttonText="Simpan">
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Return <span class="text-error">*</span></label>
        <select name="return_type" id="addReturnType" class="w-full border rounded p-2" required
            onchange="handleReturnTypeChange('add')">
            <option value="">-- Pilih Tipe --</option>
            <option value="masuk">Return Barang Masuk (dari Supplier)</option>
            <option value="keluar">Return Barang Keluar (dari Proyek/Konsumen)</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Barang <span class="text-error">*</span></label>
        <select name="id_item" class="w-full border rounded p-2" required>
            <option value="">-- Pilih Barang --</option>
            @foreach ($items as $item)
                <option value="{{ $item->id_item }}">{{ $item->id_item }} - {{ $item->name_item }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
            <input type="number" name="quantity" value="0" class="w-full border rounded p-2" min="1"
                required>
        </div>
        <div>
            <label class="block text-text-primary mb-1" id="refLabel">Referensi Dokumen (Opsional)</label>
            <div id="addRefContainer">
                <select name="id_stock_out" id="addStockOutSelect" class="w-full border rounded p-2">
                    <option value="">-- Pilih Referensi --</option>
                    @foreach ($stockOuts as $out)
                        <option value="{{ $out->id_stock_out }}">{{ $out->id_stock_out }} - {{ $out->item->name_item }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alasan Return</label>
        <input type="text" name="alasan" class="w-full border rounded p-2" placeholder="Rusak, Tidak sesuai, dll">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border rounded p-2" required>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="keterangan" class="w-full border rounded p-2" rows="3" placeholder="Masukkan keterangan..."></textarea>
    </div>
</x-modal>

<script>
    function handleReturnTypeChange(type) {
        const returnTypeSelect = document.getElementById(type + 'ReturnType');
        const refContainer = document.getElementById(type + 'RefContainer');
        const refLabel = document.getElementById('refLabel');

        if (!returnTypeSelect || !refContainer) return;

        let stockOutSelect = document.getElementById(type + 'StockOutSelect');
        let stockInSelect = document.getElementById(type + 'StockInSelect');

        if (returnTypeSelect.value === 'masuk') {
            refLabel.textContent = 'Barang Masuk (Opsional)';
            refContainer.innerHTML = `
            <select name="id_stock_in" id="` + type + `StockInSelect" class="w-full border rounded p-2">
                <option value="">-- Pilih Referensi --</option>
                @foreach ($stockIns as $in)
                    <option value="{{ $in->id_stock_in }}">{{ $in->id_stock_in }}</option>
                @endforeach
            </select>
        `;
        } else if (returnTypeSelect.value === 'keluar') {
            refLabel.textContent = 'Barang Keluar (Opsional)';
            refContainer.innerHTML = `
            <select name="id_stock_out" id="` + type + `StockOutSelect" class="w-full border rounded p-2">
                <option value="">-- Pilih Referensi --</option>
                @foreach ($stockOuts as $out)
                    <option value="{{ $out->id_stock_out }}">{{ $out->id_stock_out }}</option>
                @endforeach
            </select>
        `;
        }
    }
</script>
