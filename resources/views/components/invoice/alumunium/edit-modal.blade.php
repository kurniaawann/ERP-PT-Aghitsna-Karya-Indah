{{-- Modal Edit Invoice Alumunium --}}
<x-modal id="editModal-{{ $invoice->invoice_number }}" title="Edit Invoice"
    action="{{ route('alumunium-invoice.update', $invoice->invoice_number) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">No Invoice</label>
        <input type="text" value="{{ $invoice->invoice_number }}"
            class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed" readonly>
        <p class="text-xs text-gray-500 mt-1">No Invoice tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Kepada <span class="text-error">*</span></label>
        <input type="text" name="recipient" value="{{ $invoice->recipient }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Hal / Regarding <span class="text-error">*</span></label>
        <input type="text" name="regarding" value="{{ $invoice->regarding }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Hal/Regarding tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Deskripsi Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2" required
            oninvalid="this.setCustomValidity('Deskripsi proyek tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $invoice->project_description }}</textarea>
    </div>

    <div id="items-container-edit-{{ $invoice->invoice_number }}" class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="items-list-edit-{{ $invoice->invoice_number }}">
            @php
                $existingItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
            @endphp
            @foreach ($existingItems as $index => $item)
                <div class="item-row-edit mb-3 p-3 border rounded bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                        <input type="text" name="items[{{ $index }}][keterangan]"
                            value="{{ $item['keterangan'] ?? '' }}"
                            class="item-keterangan-edit border rounded p-2 w-full" placeholder="Keterangan" required
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][volume]"
                            value="{{ $item['volume'] ?? 0 }}" class="item-volume-edit border rounded p-2 w-full"
                            placeholder="Volume" required oninput="calculateEditRowTotal(this)"
                            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')"
                            oninput="calculateEditRowTotal(this); this.setCustomValidity('')">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input type="text" name="items[{{ $index }}][satuan]"
                            value="{{ $item['satuan'] ?? '' }}" class="item-satuan-edit border rounded p-2 w-full"
                            placeholder="Satuan" required
                            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][harga]"
                            value="{{ $item['harga'] ?? 0 }}" class="item-harga-edit border rounded p-2 w-full"
                            placeholder="Harga" required oninput="calculateEditRowTotal(this)"
                            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')"
                            oninput="calculateEditRowTotal(this); this.setCustomValidity('')">
                        <div class="flex items-center">
                            <span class="item-total-edit text-sm font-semibold text-primary">
                                Rp {{ number_format(($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}
                            </span>
                        </div>
                        <button type="button"
                            class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="add-item-edit bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover"
            data-invoice-id="{{ $invoice->invoice_number }}">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    <!-- Live Total Preview for Edit -->
    <div class="mb-4 p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-gray-700 font-semibold">Total Invoice:</span>
            <span id="invoice-total-preview-edit-{{ $invoice->invoice_number }}"
                class="text-2xl font-bold text-primary">
                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <!-- Discount Section -->
    <div class="mb-3 p-3 border rounded bg-yellow-50">
        <label class="block text-gray-700 font-semibold mb-2">Discount (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-gray-600 text-sm mb-1">Tipe Discount</label>
                <select name="discount_type" class="w-full border rounded p-2">
                    <option value="">Tidak Ada Discount</option>
                    <option value="percentage" {{ $invoice->discount_type === 'percentage' ? 'selected' : '' }}>
                        Persentase (%)</option>
                    <option value="amount" {{ $invoice->discount_type === 'amount' ? 'selected' : '' }}>Nominal (Rp)
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-gray-600 text-sm mb-1">Nilai Discount</label>
                <input type="number" step="0.01" min="0" name="discount_value"
                    value="{{ $invoice->discount_value ?? 0 }}" class="w-full border rounded p-2" placeholder="0">
            </div>
        </div>
    </div>

    <!-- DP Section -->
    <div class="mb-3 p-3 border rounded bg-blue-50">
        <label class="block text-gray-700 font-semibold mb-2">DP / Uang Muka (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-gray-600 text-sm mb-1">Tipe DP</label>
                <select name="dp_type" class="w-full border rounded p-2">
                    <option value="">Tidak Ada DP</option>
                    <option value="percentage" {{ $invoice->dp_type === 'percentage' ? 'selected' : '' }}>Persentase
                        (%)</option>
                    <option value="amount" {{ $invoice->dp_type === 'amount' ? 'selected' : '' }}>Nominal (Rp)
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-gray-600 text-sm mb-1">Nilai DP</label>
                <input type="number" step="0.01" min="0" name="dp_value"
                    value="{{ $invoice->dp_value ?? 0 }}" class="w-full border rounded p-2" placeholder="0">
            </div>
        </div>
    </div>

    <!-- Payment Accounts Selection -->
    <div class="mb-3 p-3 border rounded bg-green-50">
        <label class="block text-gray-700 font-semibold mb-2">
            Pilih Rekening Pembayaran <span class="text-error">*</span>
            <span class="text-xs font-normal text-gray-600">(Minimal 1 rekening harus dipilih)</span>
        </label>
        <div class="space-y-2">
            @php
                $selectedAccounts = is_string($invoice->selected_payment_accounts)
                    ? json_decode($invoice->selected_payment_accounts, true)
                    : $invoice->selected_payment_accounts ?? [];
                $paymentAccounts = \App\Models\Invoice\PaymentAccount::active()->get();
            @endphp
            @if ($paymentAccounts->count() > 0)
                @foreach ($paymentAccounts as $account)
                    <label class="flex items-start p-2 bg-white rounded border hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="mt-1 mr-3 payment-account-checkbox-edit"
                            {{ in_array($account->id, $selectedAccounts) ? 'checked' : '' }}>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">{{ $account->bank_name }}</div>
                            <div class="text-sm text-gray-600">
                                No: {{ $account->account_number }} a/n {{ $account->account_holder }}
                            </div>
                        </div>
                    </label>
                @endforeach
            @endif
        </div>
    </div>

</x-modal>
