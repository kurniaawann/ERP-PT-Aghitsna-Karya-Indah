{{-- Modal Tambah Bukti Kas Keluar --}}
<x-modal id="addModal" title="Tambah Bukti Kas Keluar" action="{{ route('cash-out-proof.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" class="w-full border rounded p-2" required value="{{ date('Y-m-d') }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe Template <span class="text-error">*</span></label>
        <select name="template_type" id="addTemplateType" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tipe template tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="standard">Standard (BUKTI KAS KELUAR)</option>
            <option value="hollow">Hollow (HOLLOW - BUKTI KAS KELUAR)</option>
        </select>
        <small class="text-gray-500 text-xs">Pilih format template yang akan digunakan</small>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Dibayarkan Kepada <span class="text-error">*</span></label>
        <input type="text" name="paid_to" class="w-full border rounded p-2" placeholder="Masukkan nama penerima"
            required maxlength="255" oninvalid="this.setCustomValidity('Dibayarkan kepada tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="amount" class="w-full border rounded p-2 cash-out-amount-input"
            placeholder="Masukkan jumlah nominal" required min="0"
            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
        <small class="text-gray-500 text-xs">Masukkan nominal dalam Rupiah (tanpa desimal)</small>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="description" class="w-full border rounded p-2" rows="4"
            placeholder="Masukkan keterangan (opsional)"></textarea>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1" id="addDirectorLabel">Direktur</label>
        <input type="text" name="director" id="addDirectorInput" class="w-full border rounded p-2"
            placeholder="Zulkarnain,ST.,MT (default)" maxlength="255">
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kabag Keuangan</label>
        <input type="text" name="finance_head" class="w-full border rounded p-2" placeholder="Kamila,AMK (default)"
            maxlength="255">
        <small class="text-gray-500 text-xs">Kosongkan untuk menggunakan nama default</small>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const templateType = document.getElementById('addTemplateType');
            const directorLabel = document.getElementById('addDirectorLabel');
            const directorInput = document.getElementById('addDirectorInput');

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
