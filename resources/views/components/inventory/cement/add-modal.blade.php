{{-- Modal Tambah Data Semen --}}
<x-modal id="addModal" title="Tambah Data Semen" action="{{ route('cement.store') }}" method="POST" buttonText="Simpan"
    size="6xl">

    {{-- Field: DO Semen --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">DO Semen</label>
        <select name="do_no" class="w-full border rounded p-2">
            <option value="">-- Pilih DO --</option>
            @foreach ($cementDeliveryOrders as $deliveryOrder)
                <option value="{{ $deliveryOrder->no }}">{{ $deliveryOrder->no }}</option>
            @endforeach
        </select>
        <p class="text-xs text-text-secondary mt-1">Opsional. Semen data dapat dikaitkan ke sebuah DO Semen.</p>
    </div>

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="tanggal" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="nama_proyek" class="w-full border rounded p-2" placeholder="Masukkan nama proyek"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Jumlah Sak --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" name="jumlah" value="0" class="w-full border rounded p-2"
            placeholder="Masukkan jumlah sak" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Satuan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Satuan</label>
        <input type="text" name="satuan" value="zak" class="w-full border rounded p-2" placeholder="cth: zak"
            maxlength="50">
    </div>

    {{-- Field: Harga Per Sak --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga <span class="text-error">*</span></label>
        <input type="text" name="harga" value="Rp 0" class="w-full border rounded p-2" placeholder="Rp 0"
            required inputmode="numeric" id="add-harga"
            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Harga per sak. Total = Harga x Jumlah sak.</p>
    </div>

    {{-- Field: Tanggal Lunas --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Lunas</label>
        <input type="date" name="tanggal_lunas" class="w-full border rounded p-2">
    </div>
</x-modal>
