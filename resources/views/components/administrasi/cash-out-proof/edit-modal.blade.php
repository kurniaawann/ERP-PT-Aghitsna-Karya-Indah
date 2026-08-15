{{-- ============================================================
     KOMPONEN MODAL EDIT BUKTI KAS KELUAR
     Form untuk mengedit data bukti kas keluar yang sudah ada.

     Field Form:
     - BKK No. (read-only, hanya tampilan)
     - Cek No. (read-only, hanya tampilan)
     - Tanggal (date picker)
     - Tipe Template (dropdown: standard/hollow/bkc)
     - Dibayarkan Kepada (text input)
     - Jumlah (Rp) (numeric input dengan format Rupiah)
     - Keterangan (textarea, opsional)
     - Direktur/Manager (text input)
     - Kabag Keuangan (text input)

     Catatan:
     - Modal diidentifikasi menggunakan ID unik: editModal-{bkk_no}
     - Label Direktur/Manager berubah otomatis berdasarkan tipe template yang dipilih
     - Nilai awal label Direktur/Manager disesuaikan dengan template_type saat ini
============================================================ --}}

<x-modal id="editModal-{{ $cashOut->bkk_no }}" title="Edit Bukti Kas Keluar"
    action="{{ route('cash-out-proof.update', $cashOut->bkk_no) }}" method="PUT" buttonText="Update">

    {{-- Options petinggi (dari modul Data Petinggi) untuk dropdown
         Direktur/Manager, Kabag Keuangan, & Diterima Oleh. Nilai yang
         dikirim adalah ID petinggi; disimpan sebagai snapshot signatures.
         Untuk data lama tanpa snapshot, nilai saat ini (nama) dicocokkan
         dengan petinggi agar dropdown tetap terpilih. --}}
    @php
        $executiveOptions = $executives->map(fn ($e) => [
            'value' => (string) $e->id,
            'label' => $e->name.($e->position ? ' — '.$e->position : ''),
        ])->values();

        $executiveByName = $executives->keyBy('name');
        $storedSignatures = is_array($cashOut->signatures) ? $cashOut->signatures : [];

        $selectedDirektur = $storedSignatures['direktur']['id'] ?? null;
        if (! $selectedDirektur && $cashOut->director && $executiveByName->has($cashOut->director)) {
            $selectedDirektur = $executiveByName[$cashOut->director]->id;
        }

        $selectedFinanceHead = $storedSignatures['kabag_keuangan']['id'] ?? null;
        if (! $selectedFinanceHead && $cashOut->finance_head && $executiveByName->has($cashOut->finance_head)) {
            $selectedFinanceHead = $executiveByName[$cashOut->finance_head]->id;
        }

        $selectedReceivedBy = $storedSignatures['diterima_oleh']['id'] ?? null;
    @endphp

    {{-- Field: BKK No. (read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">BKK No.</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100"
            value="{{ $cashOut->bkk_no }}" disabled>
    </div>

    {{-- Field: Cek No. (read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Cek No.</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100"
            value="{{ $cashOut->cek_no }}" disabled>
    </div>

    {{-- Field: Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" class="w-full border rounded p-2" required
            value="{{ $cashOut->date->format('Y-m-d') }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Tipe Template (Standard / Hollow / BKC) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Template <span class="text-error">*</span></label>
        <select name="template_type" id="editTemplateType-{{ $cashOut->bkk_no }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tipe template tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="standard" {{ $cashOut->template_type == 'standard' ? 'selected' : '' }}>
                Standard (BUKTI KAS KELUAR)
            </option>
            @if (auth()->user()->isSuperAdmin() || $cashOut->template_type == 'hollow')
                <option value="hollow" {{ $cashOut->template_type == 'hollow' ? 'selected' : '' }}>
                    Hollow (HOLLOW - BUKTI KAS KELUAR)
                </option>
            @endif
            <option value="bkc" {{ $cashOut->template_type == 'bkc' ? 'selected' : '' }}>
                Bukti Cek/Giro (BUKTI CEK/GIRO KELUAR)
            </option>
        </select>
        <small class="text-gray-500 text-xs">Pilih format template yang akan digunakan</small>
    </div>

    {{-- Field: Dibayarkan Kepada --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Dibayarkan Kepada <span class="text-error">*</span></label>
        <input type="text" name="paid_to" class="w-full border rounded p-2"
            placeholder="Masukkan nama penerima" required maxlength="255"
            value="{{ $cashOut->paid_to }}"
            oninvalid="this.setCustomValidity('Dibayarkan kepada tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Jumlah (Rp) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount"
            class="w-full border rounded p-2 cash-out-amount-input"
            placeholder="Masukkan jumlah nominal" required min="0"
            value="{{ $cashOut->amount }}"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
        <small class="text-gray-500 text-xs">Masukkan nominal dalam Rupiah (tanpa desimal)</small>
    </div>

    {{-- Field: Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="description" class="w-full border rounded p-2" rows="4"
            placeholder="Masukkan keterangan (opsional)">{{ $cashOut->description }}</textarea>
    </div>

    {{-- Field: Direktur / Manager (dari Data Petinggi, label berubah sesuai tipe template) --}}
    <div class="mb-3">
        <x-forms.searchable-select name="signatures[direktur]" id="editDirector-{{ $cashOut->bkk_no }}"
            label="{{ $cashOut->template_type == 'hollow' ? 'Manager' : 'Direktur' }}"
            placeholder="Cari petinggi..." :options="$executiveOptions"
            :selected="$selectedDirektur" />
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    {{-- Field: Kabag Keuangan (dari Data Petinggi) --}}
    <div class="mb-3">
        <x-forms.searchable-select name="signatures[kabag_keuangan]" id="editFinanceHead-{{ $cashOut->bkk_no }}"
            label="Kabag Keuangan" placeholder="Cari petinggi..." :options="$executiveOptions"
            :selected="$selectedFinanceHead" />
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    {{-- Field: Diterima Oleh (dari Data Petinggi) --}}
    <div class="mb-3">
        <x-forms.searchable-select name="signatures[diterima_oleh]" id="editReceivedBy-{{ $cashOut->bkk_no }}"
            label="Diterima Oleh" placeholder="Cari petinggi..." :options="$executiveOptions"
            :selected="$selectedReceivedBy" />
        <small class="text-gray-500 text-xs">Pilih petinggi yang menerima uang (opsional)</small>
    </div>
</x-modal>
