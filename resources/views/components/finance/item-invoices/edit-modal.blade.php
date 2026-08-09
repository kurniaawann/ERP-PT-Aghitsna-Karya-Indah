{{-- Modal Edit Invoice Barang --}}
<x-modal id="editModal-{{ $invoice->invoice_number }}" title="Edit Invoice Barang"
    action="{{ route('item-invoice.update', $invoice->invoice_number) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No Invoice</label>
        <input type="text" value="{{ $invoice->invoice_number }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
        <p class="text-xs text-text-secondary mt-1">No Invoice tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada (Nama Penerima) <span class="text-error">*</span></label>
        <input type="text" name="recipient" value="{{ $invoice->recipient }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Hal / Regarding <span class="text-error">*</span></label>
        <input type="text" name="regarding" value="{{ $invoice->regarding }}" class="w-full border rounded p-2"
            placeholder="Contoh: Penagihan Pembayaran" required
            oninvalid="this.setCustomValidity('Hal/Regarding tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Deskripsi Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2" required
            oninvalid="this.setCustomValidity('Deskripsi proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">{{ $invoice->project_description }}</textarea>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek</label>
        <input type="text" name="proyek" value="{{ $invoice->proyek }}" class="w-full border rounded p-2"
            placeholder="Contoh: Rumah Kost" oninput="this.setCustomValidity('')">
    </div>

    <div id="barang-items-container-edit-{{ $invoice->invoice_number }}" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="barang-items-list-edit-{{ $invoice->invoice_number }}" class="space-y-3">
            @php
                $existingItems = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
            @endphp
            @foreach ($existingItems as $index => $item)
                <div class="barang-item-row-edit mb-3 p-3 border rounded bg-surface-secondary"
                    data-index="{{ $index }}">
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="barang-from-stock-edit accent-primary"
                                {{ !empty($item['from_stock']) ? 'checked' : '' }}>
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    <div class="relative mb-2 barang-select-wrapper-edit"
                        style="display: {{ !empty($item['from_stock']) ? 'block' : 'none' }};">
                        <input type="text"
                            class="barang-search-input-edit w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                            placeholder="Cari barang..." autocomplete="off"
                            value="{{ !empty($item['id_item']) ? ($item['name_item'] ?? '') : '' }}">
                        <i
                            class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

                        <div
                            class="barang-dropdown-edit absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="barang-options-edit">
                                <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b"
                                    data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $stockItem)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light barang-option-edit"
                                        data-value="{{ $stockItem->id_item }}"
                                        data-name="{{ $stockItem->name_item }}"
                                        data-capital="{{ $stockItem->capital_price }}"
                                        data-selling="{{ $stockItem->selling_price }}"
                                        data-stock="{{ $stockItem->quantity }}"
                                        data-search="{{ strtolower($stockItem->name_item) }}">
                                        <div class="font-medium text-text-heading">
                                            {{ $stockItem->name_item }}
                                        </div>
                                        <div class="text-xs text-text-secondary mt-1">
                                            Stok: <span
                                                class="font-semibold text-primary">{{ $stockItem->quantity }}</span>
                                            unit
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="barang-no-results-edit p-4 text-center text-sm text-text-secondary hidden">
                                <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                                <p>Tidak ada barang ditemukan</p>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" class="barang-select-hidden-edit" value="{{ $item['id_item'] ?? '' }}">

                    <input type="text" name="items[{{ $index }}][name_item]"
                        value="{{ $item['name_item'] ?? '' }}"
                        class="barang-item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *"
                        {{ !empty($item['from_stock']) ? 'readonly' : '' }} required
                        oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">

                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="items[{{ $index }}][quantity]"
                            value="{{ $item['quantity'] ?? 0 }}" class="barang-item-qty-edit border rounded p-2"
                            placeholder="Qty *" required min="1"
                            oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" name="items[{{ $index }}][capital_price]"
                            value="Rp {{ number_format($item['capital_price'] ?? 0, 0, ',', '.') }}"
                            class="barang-item-capital-edit border rounded p-2" placeholder="Rp 0"
                            {{ !empty($item['from_stock']) ? 'readonly' : '' }} required
                            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" name="items[{{ $index }}][selling_price]"
                            value="Rp {{ number_format($item['selling_price'] ?? 0, 0, ',', '.') }}"
                            class="barang-item-selling-edit border rounded p-2" placeholder="Rp 0"
                            {{ !empty($item['from_stock']) ? 'readonly' : '' }} required
                            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    </div>

                    <p class="barang-stock-warning-edit text-error text-sm mt-2 hidden">
                        <span class="font-semibold">Peringatan Stok:</span> <span
                            class="barang-stock-warning-text-edit">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan
                            Stok Yang Tersedia.</span>
                    </p>

                    <p class="barang-price-warning-edit text-error text-sm mt-2 hidden">
                        <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama
                        dengan harga jual!
                    </p>

                    <input type="hidden" name="items[{{ $index }}][from_stock]"
                        class="barang-from-stock-hidden"
                        value="{{ !empty($item['from_stock']) ? 'true' : 'false' }}">
                    <input type="hidden" name="items[{{ $index }}][id_item]" class="barang-id-item-hidden"
                        value="{{ $item['id_item'] ?? '' }}">

                    <button type="button"
                        class="remove-barang-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                </div>
            @endforeach
        </div>
        <button type="button"
            class="add-barang-item-edit bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover w-full mt-2"
            data-invoice-number="{{ $invoice->invoice_number }}">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    {{-- Tanda Tangan (Opsional) --}}
    <div class="mb-3 p-3 border rounded bg-purple-50">
        <label class="block text-text-primary font-semibold mb-2">Tanda Tangan (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Nama Penandatangan</label>
                <select name="signed_by_id" class="w-full border rounded p-2">
                    <option value="">-- Pilih Nama Penandatangan --</option>
                    @foreach ($executives as $executive)
                        <option value="{{ $executive->id }}"
                            {{ (int) $invoice->signed_by_id === (int) $executive->id ? 'selected' : '' }}>
                            {{ $executive->name }} ({{ $executive->position }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Divisi</label>
                <select name="division_id" class="w-full border rounded p-2">
                    <option value="">-- Pilih Divisi --</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}"
                            {{ (int) $invoice->division_id === (int) $division->id ? 'selected' : '' }}>
                            {{ $division->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Pilihan Rekening Pembayaran --}}
    <div class="mb-3 p-3 border rounded bg-green-50">
        <label class="block text-text-primary font-semibold mb-2">
            Pilih Rekening Pembayaran <span class="text-error">*</span>
            <span class="text-xs font-normal text-text-label">(Minimal 1 rekening harus dipilih)</span>
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
                        class="flex items-start p-2 bg-white rounded border hover:bg-surface-secondary cursor-pointer">
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
