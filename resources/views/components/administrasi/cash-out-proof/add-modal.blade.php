{{-- ============================================================
     KOMPONEN MODAL TAMBAH BUKTI KAS KELUAR
     Form untuk menambah data bukti kas keluar baru.

     Field Form:
     - Tanggal (date picker, default: hari ini)
     - Tipe Template (dropdown: standard/hollow/bkc)
     - Dibayarkan Kepada (text input)
     - Jumlah (Rp) (numeric input dengan format Rupiah)
     - Keterangan (textarea, opsional)
     - Direktur/Manager (dropdown Data Petinggi, opsional)
     - Kabag Keuangan (dropdown Data Petinggi, opsional)
     - Diterima Oleh (dropdown Data Petinggi, opsional)

     Catatan:
     - Label Direktur/Manager berubah otomatis berdasarkan tipe template yang dipilih
     - Format jumlah menggunakan Intl.NumberFormat('id-ID') untuk format Rupiah
============================================================ --}}

<x-modal id="addModal" title="Tambah Bukti Kas Keluar" action="{{ route('cash-out-proof.store') }}"
    method="POST" buttonText="Simpan">

    {{-- Options petinggi (dari modul Data Petinggi) untuk dropdown
         Direktur/Manager, Kabag Keuangan, & Diterima Oleh. Nilai yang
         dikirim adalah ID petinggi; disimpan sebagai snapshot signatures. --}}
    @php
        $executiveOptions = $executives->map(fn ($e) => [
            'value' => (string) $e->id,
            'label' => $e->name.($e->position ? ' — '.$e->position : ''),
        ])->values();
    @endphp

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" class="w-full border rounded p-2" required value="{{ date('Y-m-d') }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Tipe Template (Standard / Hollow / BKC) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Template <span class="text-error">*</span></label>
        <select name="template_type" id="addTemplateType" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tipe template tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="standard">Standard (BUKTI KAS KELUAR)</option>
            @if (auth()->user()->isSuperAdmin())
                <option value="hollow">Hollow (HOLLOW - BUKTI KAS KELUAR)</option>
            @endif
            <option value="bkc">Bukti Cek/Giro (BUKTI CEK/GIRO KELUAR)</option>
        </select>
        <small class="text-gray-500 text-xs">Pilih format template yang akan digunakan</small>
    </div>

    {{-- Field: Dibayarkan Kepada --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Dibayarkan Kepada <span class="text-error">*</span></label>
        <input type="text" name="paid_to" class="w-full border rounded p-2"
            placeholder="Masukkan nama penerima" required maxlength="255"
            oninvalid="this.setCustomValidity('Dibayarkan kepada tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Jumlah (Rp) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount"
            class="w-full border rounded p-2 cash-out-amount-input"
            placeholder="Masukkan jumlah nominal" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
        <small class="text-gray-500 text-xs">Masukkan nominal dalam Rupiah (tanpa desimal)</small>
    </div>

    {{-- Field: Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="description" class="w-full border rounded p-2" rows="4"
            placeholder="Masukkan keterangan (opsional)"></textarea>
    </div>

    {{-- Field: Direktur / Manager (dari Data Petinggi, label berubah sesuai tipe template) --}}
    <div class="mb-3">
        <x-forms.searchable-select name="signatures[direktur]" id="addDirector" label="Direktur"
            placeholder="Cari petinggi..." :options="$executiveOptions" />
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    {{-- Field: Kabag Keuangan (dari Data Petinggi) --}}
    <div class="mb-3">
        <x-forms.searchable-select name="signatures[kabag_keuangan]" id="addFinanceHead" label="Kabag Keuangan"
            placeholder="Cari petinggi..." :options="$executiveOptions" />
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    {{-- Field: Diterima Oleh (dari Data Petinggi) --}}
    <div class="mb-3">
        <x-forms.searchable-select name="signatures[diterima_oleh]" id="addReceivedBy" label="Diterima Oleh"
            placeholder="Cari petinggi..." :options="$executiveOptions" />
        <small class="text-gray-500 text-xs">Pilih petinggi yang menerima uang (opsional)</small>
    </div>
</x-modal>
