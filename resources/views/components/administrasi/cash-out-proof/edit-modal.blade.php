{{-- ============================================================
     KOMPONEN MODAL EDIT BUKTI KAS KELUAR
     Form untuk mengedit data bukti kas keluar yang sudah ada.

     Field Form:
     - BKK No. (read-only, hanya tampilan)
     - Cek No. (read-only, hanya tampilan)
     - Tanggal (date picker)
     - Tipe Template (dropdown: standard/hollow)
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

    {{-- Field: Tipe Template (Standard / Hollow) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Template <span class="text-error">*</span></label>
        <select name="template_type" id="editTemplateType-{{ $cashOut->bkk_no }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tipe template tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="standard" {{ $cashOut->template_type == 'standard' ? 'selected' : '' }}>
                Standard (BUKTI KAS KELUAR)
            </option>
            <option value="hollow" {{ $cashOut->template_type == 'hollow' ? 'selected' : '' }}>
                Hollow (HOLLOW - BUKTI KAS KELUAR)
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

    {{-- Field: Direktur / Manager (label berubah berdasarkan tipe template) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1"
            id="editDirectorLabel-{{ $cashOut->bkk_no }}">
            {{ $cashOut->template_type == 'hollow' ? 'Manager' : 'Direktur' }}
        </label>
        <input type="text" name="director" id="editDirectorInput-{{ $cashOut->bkk_no }}"
            class="w-full border rounded p-2"
            placeholder="{{ $cashOut->template_type == 'hollow' ? 'SISWORO SUBENO (default)' : 'Zulkarnain,ST.,MT (default)' }}"
            maxlength="255" value="{{ $cashOut->director }}">
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    {{-- Field: Kabag Keuangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kabag Keuangan</label>
        <input type="text" name="finance_head" class="w-full border rounded p-2"
            placeholder="Kamila,AMK (default)" maxlength="255"
            value="{{ $cashOut->finance_head }}">
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    {{-- Inisialisasi: Ubah label Direktur/Manager saat tipe template berubah --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const templateType = document.getElementById('editTemplateType-{{ $cashOut->bkk_no }}');
            const directorLabel = document.getElementById('editDirectorLabel-{{ $cashOut->bkk_no }}');
            const directorInput = document.getElementById('editDirectorInput-{{ $cashOut->bkk_no }}');

            if (templateType && directorLabel && directorInput) {
                templateType.addEventListener('change', function() {
                    if (this.value === 'hollow') {
                        directorLabel.textContent = 'Manager';
                        directorInput.placeholder = 'SISWORO SUBENO (default)';
                    } else {
                        directorLabel.textContent = 'Direktur';
                        directorInput.placeholder = 'Zulkarnain,ST.,MT (default)';
                    }
                });
            }
        });
    </script>
</x-modal>
