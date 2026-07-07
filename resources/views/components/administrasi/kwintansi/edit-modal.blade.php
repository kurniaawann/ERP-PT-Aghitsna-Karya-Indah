{{-- Modal Edit Kwintansi --}}
<x-modal id="editModal-{{ $kwintansi->id_kwintansi }}" title="Edit Kwintansi"
    action="{{ route('kwintansi.update', $kwintansi->id_kwintansi) }}" method="POST" buttonText="Update">
    @method('PUT')

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Kwintansi</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" value="{{ $kwintansi->id_kwintansi }}"
            disabled>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sudah Terima Dari <span class="text-error">*</span></label>
        <input type="text" name="received_from" class="w-full border rounded p-2"
            value="{{ $kwintansi->received_from }}" required maxlength="255"
            oninvalid="this.setCustomValidity('Sudah terima dari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Uang Pembayaran <span class="text-error">*</span></label>
        <textarea name="payment_for" class="w-full border rounded p-2" rows="3" required
            oninvalid="this.setCustomValidity('Uang pembayaran tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $kwintansi->payment_for }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
            <input type="text" inputmode="numeric" name="amount" class="w-full border rounded p-2" placeholder="Rp 0"
                value="Rp {{ number_format($kwintansi->amount, 0, ',', '.') }}" required
                oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
                oninput="formatCurrencyInput(this); this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Sisa (Rp)</label>
            <input type="text" inputmode="numeric" name="remaining" class="w-full border rounded p-2" placeholder="Rp 0"
                value="Rp {{ number_format($kwintansi->remaining, 0, ',', '.') }}"
                oninput="formatCurrencyInput(this)">
        </div>
    </div>

    <div class="mb-3">
        <div class="flex items-center gap-2 mb-2">
            <input type="checkbox" name="include_bank" id="include_bank_edit_{{ $kwintansi->id_kwintansi }}"
                value="1" {{ $kwintansi->include_bank ? 'checked' : '' }}
                class="w-4 h-4 accent-primary cursor-pointer">
            <label for="include_bank_edit_{{ $kwintansi->id_kwintansi }}" class="text-text-primary cursor-pointer">
                Tampilkan Bank di PDF
            </label>
        </div>
    </div>

    <div class="mb-3" id="bank_section_edit_{{ $kwintansi->id_kwintansi }}"
        style="display: {{ $kwintansi->include_bank ? 'block' : 'none' }}">
        <label class="block text-text-primary mb-1">Bank <span class="text-error">*</span></label>
        <select name="payment_account_id" class="w-full border rounded p-2"
            {{ $kwintansi->include_bank ? 'required' : '' }}
            oninvalid="this.setCustomValidity('Bank tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Bank</option>
            @foreach (\App\Models\Finance\PaymentAccount::active()->get() as $account)
                <option value="{{ $account->id }}"
                    {{ $kwintansi->payment_account_id == $account->id ? 'selected' : '' }}>
                    {{ $account->bank_name }} - {{ $account->account_number }} ({{ $account->account_holder }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="kwintansi_date" class="w-full border rounded p-2"
                value="{{ $kwintansi->kwintansi_date->format('Y-m-d') }}" required
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location" class="w-full border rounded p-2" value="{{ $kwintansi->location }}"
                maxlength="100">
        </div>
    </div>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('include_bank_edit_{{ $kwintansi->id_kwintansi }}');
        const bankSection = document.getElementById('bank_section_edit_{{ $kwintansi->id_kwintansi }}');
        const bankSelect = bankSection.querySelector('select');

        if (checkbox) {
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
        }
    });
</script>
