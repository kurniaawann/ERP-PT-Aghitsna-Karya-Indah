{{-- Modal Edit Data Semen --}}
<x-modal id="editModal-{{ $cement->no }}" title="Edit Data Semen" action="{{ route('cement.update', $cement->no) }}"
    method="PUT" buttonText="Update">

    {{-- Field: No (Readonly) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">No</label>
        <input type="text" value="{{ $cement->no }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
        <p class="text-xs text-text-secondary mt-1">No tidak dapat diubah</p>
    </div>

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="tanggal" value="{{ $cement->tanggal?->format('Y-m-d') }}" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Nama Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
        <input type="text" name="nama_proyek" value="{{ $cement->nama_proyek }}" class="w-full border rounded p-2"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Jumlah Sak --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah <span class="text-error">*</span></label>
        <input type="number" name="jumlah" value="{{ $cement->jumlah }}" class="w-full border rounded p-2" required
            min="0" oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Harga Per Sak --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Harga <span class="text-error">*</span></label>
        <input type="text" name="harga"
            value="Rp {{ number_format($cement->harga ?? 0, 0, ',', '.') }}" class="w-full border rounded p-2"
            required inputmode="numeric" id="edit-harga-{{ $cement->no }}"
            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Harga per sak. Total = Harga x Jumlah sak.</p>
    </div>

    {{-- Field: Tanggal Lunas --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Lunas</label>
        <input type="date" name="tanggal_lunas" value="{{ $cement->tanggal_lunas?->format('Y-m-d') }}"
            class="w-full border rounded p-2">
    </div>
</x-modal>
