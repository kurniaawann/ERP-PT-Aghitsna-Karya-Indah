{{-- Modal Tambah Kwintansi --}}
<x-modal id="addModal" title="Tambah Kwintansi" action="{{ route('kwintansi.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sudah Terima Dari <span class="text-error">*</span></label>
        <input type="text" name="received_from" class="w-full border rounded p-2" placeholder="Masukkan nama pemberi"
            required maxlength="255" oninvalid="this.setCustomValidity('Sudah terima dari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Uang Pembayaran <span class="text-error">*</span></label>
        <textarea name="payment_for" class="w-full border rounded p-2" rows="3"
            placeholder="Masukkan keterangan pembayaran" required
            oninvalid="this.setCustomValidity('Uang pembayaran tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
            <input type="number" name="amount" class="w-full border rounded p-2" placeholder="0" required
                min="0" oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Sisa (Rp)</label>
            <input type="number" name="remaining" class="w-full border rounded p-2" placeholder="0" min="0">
        </div>
    </div>

    <div class="mb-3">
        <div class="flex items-center gap-2 mb-2">
            <input type="checkbox" name="include_bank" id="include_bank_add" value="1" checked
                class="w-4 h-4 accent-primary cursor-pointer">
            <label for="include_bank_add" class="text-text-primary cursor-pointer">Tampilkan Bank di PDF</label>
        </div>
    </div>

    <div class="mb-3" id="bank_section_add">
        <label class="block text-text-primary mb-1">Bank <span class="text-error">*</span></label>
        <select name="payment_account_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Bank tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Bank</option>
            @foreach (\App\Models\Finance\PaymentAccount::active()->get() as $account)
                <option value="{{ $account->id }}">
                    {{ $account->bank_name }} - {{ $account->account_number }} ({{ $account->account_holder }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="kwintansi_date" class="w-full border rounded p-2" required
                value="{{ date('Y-m-d') }}" oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location" class="w-full border rounded p-2" placeholder="Depok (default)"
                maxlength="100">
            <small class="text-gray-500 text-xs">Kosongkan jika di Depok</small>
        </div>
    </div>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('include_bank_add');
        const bankSection = document.getElementById('bank_section_add');
        const bankSelect = bankSection.querySelector('select');

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                bankSection.style.display = 'block';
                bankSelect.required = true;
            } else {
                bankSection.style.display = 'none';
                bankSelect.required = false;
                bankSelect.value = '';
            }
        });
    });
</script>
