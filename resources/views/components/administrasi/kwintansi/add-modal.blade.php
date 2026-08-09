{{-- Modal Tambah Kwintansi --}}
<x-modal id="addModal" title="Tambah Kwintansi" action="{{ route('kwintansi.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Sudah Terima Dari <span class="text-error">*</span></label>
        <input type="text" name="received_from" class="w-full border rounded p-2" placeholder="Masukkan nama pemberi"
            required maxlength="255" oninvalid="this.setCustomValidity('Sudah terima dari tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan <span class="text-error">*</span></label>
        <textarea name="payment_for" class="w-full border rounded p-2" rows="3"
            placeholder="Masukkan keterangan pembayaran" required
            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Jumlah (Rp) <span class="text-error">*</span></label>
            <input type="text" inputmode="numeric" name="amount" class="w-full border rounded p-2" placeholder="Rp 0"
                required oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')"
                oninput="formatCurrencyInput(this); this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Sisa (Rp)</label>
            <input type="text" inputmode="numeric" name="remaining" class="w-full border rounded p-2" placeholder="Rp 0"
                oninput="formatCurrencyInput(this)">
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="kwintansi_date" class="w-full border rounded p-2" required
            value="{{ date('Y-m-d') }}" oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Tanda Tangan --}}
    <div class="mb-3 p-3 border rounded bg-purple-50">
        <label class="block text-text-primary font-semibold mb-2">Tanda Tangan</label>
        <div>
            <label class="block text-text-label text-sm mb-1">Nama Tanda Tangan</label>
            <select name="signed_by_id" class="w-full border rounded p-2">
                <option value="">-- Pilih Nama Tanda Tangan --</option>
                @foreach ($executives as $executive)
                    <option value="{{ $executive->id }}">{{ $executive->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Pilih Rekening (Opsional) --}}
    <div class="mb-3 p-3 border rounded bg-green-50">
        <label class="block text-text-primary font-semibold mb-2">
            Pilih Rekening <span class="text-xs font-normal text-text-label">(Opsional, bisa lebih dari satu)</span>
        </label>
        <div class="space-y-2">
            @if (isset($paymentAccounts) && $paymentAccounts->count() > 0)
                @foreach ($paymentAccounts as $account)
                    <label
                        class="flex items-start p-2 bg-white rounded border hover:bg-surface-secondary cursor-pointer">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="mt-1 mr-3 accent-primary">
                        <div class="flex-1">
                            <div class="font-semibold text-text-heading">{{ $account->bank_name }}</div>
                            <div class="text-sm text-text-label">
                                No: {{ $account->account_number }} a/n {{ $account->account_holder }}
                            </div>
                        </div>
                    </label>
                @endforeach
            @else
                <div class="p-3 bg-yellow-100 border border-yellow-300 rounded text-sm">
                    <i class="fa-solid fa-exclamation-triangle text-yellow-600"></i>
                    Belum ada rekening pembayaran.
                    <a href="{{ route('payment-accounts.index') }}" class="text-blue-600 hover:underline"
                        target="_blank">
                        Tambah rekening pembayaran
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-modal>
