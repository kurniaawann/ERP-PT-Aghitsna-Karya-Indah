{{-- Modal Edit Invoice Proyek --}}
<x-modal id="editModal-{{ $invoice->invoice_number }}" title="{{ auth()->user()->isAdmin() ? 'Edit Invoice' : 'Edit Invoice Proyek' }}"
    action="{{ route('proyek-invoice.update', $invoice->invoice_number) }}" method="PUT" buttonText="Update">

    {{-- Nomor Invoice (Read-only) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">No Invoice</label>
        <input type="text" value="{{ $invoice->invoice_number }}"
            class="w-full border border-border-strong rounded-lg p-2 bg-surface-hover text-text-input cursor-not-allowed"
            readonly>
        <p class="text-xs text-text-secondary mt-1">No Invoice tidak dapat diubah</p>
    </div>

    {{-- Informasi Invoice --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}"
            class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" required
            oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada <span class="text-error">*</span></label>
        <input type="text" name="recipient" value="{{ $invoice->recipient }}"
            class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" required
            oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Hal / Regarding <span class="text-error">*</span></label>
        <input type="text" name="regarding" value="{{ $invoice->regarding }}"
            class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" required
            oninvalid="this.setCustomValidity('Hal/Regarding tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Deskripsi Proyek <span class="text-error">*</span></label>
        <textarea name="project_description"
            class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input" rows="2" required
            oninvalid="this.setCustomValidity('Deskripsi proyek tidak boleh kosong')" oninput="this.setCustomValidity('')">{{ $invoice->project_description }}</textarea>
    </div>

    {{-- Detail Item Invoice --}}
    <div id="items-container-edit-{{ $invoice->invoice_number }}" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="items-list-edit-{{ $invoice->invoice_number }}">
            @php
                $existingItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
            @endphp
            @foreach ($existingItems as $index => $item)
                <div class="item-row-edit mb-3 p-3 border border-border-strong rounded-lg bg-surface-secondary">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                        <input type="text" name="items[{{ $index }}][keterangan]"
                            value="{{ $item['keterangan'] ?? '' }}"
                            class="item-keterangan border border-border-strong rounded-lg p-2 w-full text-text-input"
                            placeholder="Keterangan *" required
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][volume]"
                            value="{{ $item['volume'] ?? 0 }}"
                            class="item-volume border border-border-strong rounded-lg p-2 w-full text-text-input"
                            placeholder="Volume *" required
                            oninput="calculateRowTotalEdit(this, '{{ $invoice->invoice_number }}'); this.setCustomValidity('')"
                            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input type="text" name="items[{{ $index }}][satuan]"
                            value="{{ $item['satuan'] ?? '' }}"
                            class="item-satuan border border-border-strong rounded-lg p-2 w-full text-text-input"
                            placeholder="Satuan *" required
                            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" name="items[{{ $index }}][harga]"
                            value="Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}"
                            class="item-harga border border-border-strong rounded-lg p-2 w-full text-text-input"
                            placeholder="Rp 0" required
                            oninput="calculateRowTotalEdit(this, '{{ $invoice->invoice_number }}'); this.setCustomValidity('')"
                            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                        <div class="flex items-center">
                            <span class="item-total text-sm font-semibold text-primary">Rp
                                {{ number_format(($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}</span>
                        </div>
                        <button type="button"
                            class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="add-item-edit-{{ $invoice->invoice_number }}"
            class="bg-btn-add text-white px-4 py-2 rounded hover:bg-btn-add-hover">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    {{-- Live Total Preview --}}
    <div class="mb-4 p-4 bg-primary-light rounded-lg border-2 border-primary-light">
        <div class="flex justify-between items-center">
            <span class="text-text-primary font-semibold">Total Invoice:</span>
            <span id="invoice-total-preview-edit-{{ $invoice->invoice_number }}"
                class="text-2xl font-bold text-primary">Rp
                {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Discount Section --}}
    <div class="mb-3 p-3 border border-warning-light rounded-lg bg-warning-light" id="discount-section-edit-{{ $invoice->invoice_number }}">
        <label class="block text-text-primary font-semibold mb-2">Discount (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Tipe Discount</label>
                <select name="discount_type" id="discount-type-edit-{{ $invoice->invoice_number }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input"
                    onchange="calculateDiscountEdit('{{ $invoice->invoice_number }}')">
                    <option value="" {{ !$invoice->discount_type ? 'selected' : '' }}>Tidak Ada Discount
                    </option>
                    <option value="percentage" {{ $invoice->discount_type == 'percentage' ? 'selected' : '' }}>
                        Persentase (%)</option>
                    <option value="amount" {{ $invoice->discount_type == 'amount' ? 'selected' : '' }}>Nominal
                        (Rp)</option>
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Nilai Discount</label>
                <input type="text" inputmode="decimal" name="discount_value"
                    id="discount-value-edit-{{ $invoice->invoice_number }}"
                    value="{{ $invoice->discount_value ?? 0 }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input disabled:bg-surface-disabled disabled:cursor-not-allowed"
                    placeholder="0"
                    oninput="formatDecimalInput(this); calculateDiscountEdit('{{ $invoice->invoice_number }}')">
                <div id="discount-error-edit-{{ $invoice->invoice_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Persentase diskon tidak boleh 100% atau lebih</span>
                </div>
                <div id="discount-amount-error-edit-{{ $invoice->invoice_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Nominal diskon tidak boleh lebih dari atau sama dengan total invoice</span>
                </div>
            </div>
        </div>
        <div class="mt-2 p-2 bg-surface-base rounded-lg" id="discount-summary-edit-{{ $invoice->invoice_number }}">
            <div class="flex justify-between">
                <span class="text-sm text-text-label">Discount:</span>
                <span id="discount-amount-edit-{{ $invoice->invoice_number }}"
                    class="text-sm font-semibold text-error">Rp 0</span>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-sm font-bold text-text-primary">Total Setelah Discount:</span>
                <span id="total-after-discount-edit-{{ $invoice->invoice_number }}"
                    class="text-sm font-bold text-success">Rp 0</span>
            </div>
        </div>
    </div>

    {{-- DP / Uang Muka Section --}}
    <div class="mb-3 p-3 border border-info-light rounded-lg bg-info-light" id="dp-section-edit-{{ $invoice->invoice_number }}">
        <label class="block text-text-primary font-semibold mb-2">DP / Uang Muka (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Tipe DP</label>
                <select name="dp_type" id="dp-type-edit-{{ $invoice->invoice_number }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input"
                    onchange="calculateDPEdit('{{ $invoice->invoice_number }}')">
                    <option value="" {{ !$invoice->dp_type ? 'selected' : '' }}>Tidak Ada DP</option>
                    <option value="percentage" {{ $invoice->dp_type == 'percentage' ? 'selected' : '' }}>
                        Persentase (%)</option>
                    <option value="amount" {{ $invoice->dp_type == 'amount' ? 'selected' : '' }}>Nominal (Rp)
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Nilai DP</label>
                <input type="text" inputmode="decimal" name="dp_value"
                    id="dp-value-edit-{{ $invoice->invoice_number }}" value="{{ $invoice->dp_value ?? 0 }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input disabled:bg-surface-disabled disabled:cursor-not-allowed"
                    placeholder="0"
                    oninput="formatDecimalInput(this); calculateDPEdit('{{ $invoice->invoice_number }}')">
                <div id="dp-error-edit-{{ $invoice->invoice_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Persentase DP tidak boleh 100% atau lebih</span>
                </div>
                <div id="dp-amount-error-edit-{{ $invoice->invoice_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Nominal DP tidak boleh lebih dari atau sama dengan total</span>
                </div>
            </div>
        </div>
        <div class="mt-2 p-2 bg-surface-base rounded-lg">
            <div class="flex justify-between">
                <span class="text-sm font-bold text-text-primary">Nilai DP:</span>
                <span id="dp-amount-edit-{{ $invoice->invoice_number }}" class="text-sm font-bold text-info">Rp
                    0</span>
            </div>
        </div>
    </div>

    {{-- Tanda Tangan (Opsional) --}}
    <div class="mb-3 p-3 border border-purple-300 rounded-lg bg-purple-50">
        <label class="block text-text-primary font-semibold mb-2">Tanda Tangan (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Nama Penandatangan</label>
                <input type="text" name="signed_by" value="{{ $invoice->signed_by }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input"
                    placeholder="Nama yang bertanda tangan">
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Divisi</label>
                <input type="text" name="division" value="{{ $invoice->division }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input"
                    placeholder="Contoh: Direktur, Manager, dll">
            </div>
        </div>
    </div>

    {{-- Pilihan Rekening Pembayaran --}}
    <div class="mb-3 p-3 border border-success-light rounded-lg bg-success-light">
        <label class="block text-text-primary font-semibold mb-2">
            Pilih Rekening Pembayaran <span class="text-error">*</span>
        </label>
        <div class="space-y-2">
            @php
                $selectedAccounts = is_string($invoice->selected_payment_accounts)
                    ? json_decode($invoice->selected_payment_accounts, true)
                    : $invoice->selected_payment_accounts;
            @endphp
            @if (isset($paymentAccounts) && $paymentAccounts->count() > 0)
                @foreach ($paymentAccounts as $account)
                    <label
                        class="flex items-start p-2 bg-surface-base rounded-lg border border-border-strong hover:bg-surface-secondary cursor-pointer">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="mt-1 mr-3 payment-account-checkbox"
                            onchange="validatePaymentSelectionEdit('{{ $invoice->invoice_number }}')"
                            {{ in_array($account->id, $selectedAccounts ?? []) ? 'checked' : '' }}>
                        <div class="flex-1">
                            <div class="font-semibold text-text-heading">{{ $account->bank_name }}</div>
                            <div class="text-sm text-text-label">
                                No: {{ $account->account_number }} a/n {{ $account->account_holder }}
                            </div>
                        </div>
                    </label>
                @endforeach
            @endif
        </div>
    </div>
</x-modal>
