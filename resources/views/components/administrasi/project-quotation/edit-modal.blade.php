{{-- =====================================================================
     Komponen Modal Edit Penawaran Proyek (Project Quotation)

     Form edit penawaran — pola identik dengan Invoice Proyek:
     - Data terisi otomatis dari penawaran yang dipilih
     - Daftar Item (flat, dinamis via JS, pre-populated)
     - Total, Discount, DP, dan PPN (opsional)
     - Tanda Tangan (opsional), Rekening Pembayaran (pre-selected)

     Seluruh elemen memakai suffix -{quotation_number} agar kalkulasi
     berjalan per modal tanpa saling mengganggu.

     Catatan: memperbarui penawaran TIDAK mengubah invoice yang sudah
     dibuat otomatis (snapshot).
     ===================================================================== --}}

<x-modal id="editModal-{{ $quotation->quotation_number }}"
    title="Edit Penawaran — {{ $quotation->quotation_number }}"
    action="{{ route('project-quotation.update', $quotation->quotation_number) }}" method="PUT"
    buttonText="Update">

    @if (auth()->user()->isAdmin())
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Lampiran</label>
            <input type="text" name="attachment" value="{{ $quotation->attachment }}"
                class="w-full border rounded p-2">
        </div>
    @endif

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ $quotation->date?->format('Y-m-d') }}"
            class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal penawaran tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Perihal (Hal)</label>
        <input type="text" name="subject" value="{{ $quotation->subject }}" class="w-full border rounded p-2">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada Yth <span class="text-error">*</span></label>
        <input type="text" name="recipient" value="{{ $quotation->recipient }}" class="w-full border rounded p-2"
            required oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">{{ auth()->user()->isAdmin() ? 'Pembangunan untuk' : 'Deskripsi Proyek' }}</label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2">{{ $quotation->project_description }}</textarea>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location" value="{{ $quotation->location }}" class="w-full border rounded p-2">
        </div>
    @endif

    <div id="items-container-edit-{{ $quotation->quotation_number }}" class="mb-4">
        <div id="items-error-edit-{{ $quotation->quotation_number }}"
            class="items-error-edit hidden mb-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span>Minimal harus ada 1 item dalam penawaran dengan data lengkap</span>
        </div>
        <label class="block text-text-primary font-semibold mb-2">Item-Item Penawaran <span
                class="text-error">*</span></label>
        <div id="items-list-edit-{{ $quotation->quotation_number }}">
            @php
                $existingItems = $quotation->items ?? [];
            @endphp
            @foreach ($existingItems as $index => $item)
                <div class="item-row-edit mb-3 p-3 border rounded bg-surface-secondary">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                        <input type="text" name="items[{{ $index }}][keterangan]"
                            value="{{ $item['keterangan'] ?? '' }}" class="item-keterangan border rounded p-2 w-full"
                            placeholder="Keterangan" required
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][volume]"
                            value="{{ $item['volume'] ?? 0 }}" class="item-volume border rounded p-2 w-full"
                            placeholder="Volume" required oninput="calculateEditRowTotal(this); this.setCustomValidity('')"
                            oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <input type="text" name="items[{{ $index }}][satuan]"
                            value="{{ $item['satuan'] ?? '' }}" class="item-satuan border rounded p-2 w-full"
                            placeholder="Satuan" required
                            oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" min="0"
                            name="items[{{ $index }}][harga]"
                            value="{{ number_format($item['harga'] ?? 0, 0, ',', '.') }}"
                            class="item-harga border rounded p-2 w-full" placeholder="Harga" required
                            oninput="calculateEditRowTotal(this); formatCurrencyInput(this); this.setCustomValidity('')"
                            oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                        <div class="flex items-center">
                            <span class="item-total text-sm font-semibold text-primary">
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
            data-quotation-id="{{ $quotation->quotation_number }}">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    <!-- Live Total Preview for Edit -->
    <div class="mb-4 p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-text-primary font-semibold">Total Penawaran:</span>
            <span id="invoice-total-preview-edit-{{ $quotation->quotation_number }}"
                class="text-2xl font-bold text-primary">
                Rp {{ number_format($quotation->total_amount, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <!-- Discount Section -->
    <div class="mb-3 p-3 border rounded bg-yellow-50"
        id="discount-section-edit-{{ $quotation->quotation_number }}">
        <label class="block text-text-primary font-semibold mb-2">Discount (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Tipe Discount</label>
                <select name="discount_type" id="discount-type-edit-{{ $quotation->quotation_number }}"
                    class="w-full border rounded p-2"
                    onchange="calculateDiscountEdit('{{ $quotation->quotation_number }}')">
                    <option value="" {{ !$quotation->discount_type ? 'selected' : '' }}>Tidak Ada Discount</option>
                    <option value="percentage" {{ $quotation->discount_type == 'percentage' ? 'selected' : '' }}>
                        Persentase (%)</option>
                    <option value="amount" {{ $quotation->discount_type == 'amount' ? 'selected' : '' }}>Nominal (Rp)
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Nilai Discount</label>
                <input type="text" inputmode="decimal" name="discount_value"
                    id="discount-value-edit-{{ $quotation->quotation_number }}"
                    value="{{ $quotation->discount_value ?? 0 }}" class="w-full border rounded p-2" placeholder="0"
                    oninput="formatDecimalInput(this); calculateDiscountEdit('{{ $quotation->quotation_number }}')">
                <div id="discount-error-edit-{{ $quotation->quotation_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Persentase diskon tidak boleh 100% atau lebih</span>
                </div>
                <div id="discount-amount-error-edit-{{ $quotation->quotation_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Nominal diskon tidak boleh lebih dari atau sama dengan total penawaran</span>
                </div>
            </div>
        </div>
        <div class="mt-2 p-2 bg-white rounded" id="discount-summary-edit-{{ $quotation->quotation_number }}">
            <div class="flex justify-between">
                <span class="text-sm text-text-label">Discount:</span>
                <span id="discount-amount-edit-{{ $quotation->quotation_number }}"
                    class="text-sm font-semibold text-red-600">Rp 0</span>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-sm font-bold text-text-primary">Total Setelah Discount:</span>
                <span id="total-after-discount-edit-{{ $quotation->quotation_number }}"
                    class="text-sm font-bold text-green-600">Rp 0</span>
            </div>
        </div>
    </div>

    {{-- DP / Uang Muka Section --}}
    <div class="mb-3 p-3 border border-info-light rounded-lg bg-info-light"
        id="dp-section-edit-{{ $quotation->quotation_number }}">
        <label class="block text-text-primary font-semibold mb-2">DP / Uang Muka (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Tipe DP</label>
                <select name="dp_type" id="dp-type-edit-{{ $quotation->quotation_number }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input"
                    onchange="calculateDPEdit('{{ $quotation->quotation_number }}')">
                    <option value="" {{ !$quotation->dp_type ? 'selected' : '' }}>Tidak Ada DP</option>
                    <option value="percentage" {{ $quotation->dp_type == 'percentage' ? 'selected' : '' }}>
                        Persentase (%)</option>
                    <option value="amount" {{ $quotation->dp_type == 'amount' ? 'selected' : '' }}>Nominal (Rp)
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Nilai DP</label>
                <input type="text" inputmode="decimal" name="dp_value"
                    id="dp-value-edit-{{ $quotation->quotation_number }}"
                    value="{{ $quotation->dp_value ?? 0 }}"
                    class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input disabled:bg-surface-disabled disabled:cursor-not-allowed"
                    placeholder="0"
                    oninput="formatDecimalInput(this); calculateDPEdit('{{ $quotation->quotation_number }}')">
                <div id="dp-error-edit-{{ $quotation->quotation_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Persentase DP tidak boleh 100% atau lebih</span>
                </div>
                <div id="dp-amount-error-edit-{{ $quotation->quotation_number }}"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Nominal DP tidak boleh lebih dari atau sama dengan total</span>
                </div>
            </div>
        </div>
        <div class="mt-2 p-2 bg-surface-base rounded-lg">
            <div class="flex justify-between">
                <span class="text-sm font-bold text-text-primary">Nilai DP:</span>
                <span id="dp-amount-edit-{{ $quotation->quotation_number }}"
                    class="text-sm font-bold text-info">Rp 0</span>
            </div>
        </div>
    </div>

    {{-- PPN Section --}}
    <div class="mb-3 p-3 border border-purple-300 rounded-lg bg-purple-50"
        id="ppn-section-edit-{{ $quotation->quotation_number }}">
        <label class="block text-text-primary font-semibold mb-2">PPN (Opsional)</label>
        <div>
            <label class="block text-text-label text-sm mb-1">Persentase PPN (%)</label>
            <input type="text" inputmode="decimal" name="ppn" id="ppn-value-edit-{{ $quotation->quotation_number }}"
                value="{{ $quotation->ppn ?? '' }}"
                class="w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input disabled:bg-surface-disabled disabled:cursor-not-allowed"
                placeholder="Contoh: 11"
                oninput="formatDecimalInput(this); calculatePPNEdit('{{ $quotation->quotation_number }}')">
            <small class="text-xs text-text-secondary">Dihitung dari total setelah diskon. Kosongkan jika tidak
                dikenakan PPN.</small>
            <div id="ppn-error-edit-{{ $quotation->quotation_number }}"
                class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                <i class="fa-solid fa-exclamation-circle"></i>
                <span>PPN tidak boleh 100% atau lebih</span>
            </div>
        </div>
        <div class="mt-2 p-2 bg-surface-base rounded-lg hidden"
            id="ppn-summary-edit-{{ $quotation->quotation_number }}">
            <div class="flex justify-between">
                <span class="text-sm text-text-label">PPN:</span>
                <span id="ppn-amount-edit-{{ $quotation->quotation_number }}"
                    class="text-sm font-semibold text-error">Rp 0</span>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-sm font-bold text-text-primary">Total Setelah PPN:</span>
                <span id="total-after-ppn-edit-{{ $quotation->quotation_number }}"
                    class="text-sm font-bold text-green-600">Rp 0</span>
            </div>
        </div>
    </div>

    <!-- Signature Section (Opsional) -->
    <div class="mb-3 p-3 border rounded bg-purple-50">
        <label class="block text-text-primary font-semibold mb-2">Tanda Tangan (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Nama Penandatangan</label>
                <select name="signed_by_id" class="w-full border rounded p-2">
                    <option value="">-- Pilih Nama Penandatangan --</option>
                    @foreach ($executives as $executive)
                        <option value="{{ $executive->id }}"
                            {{ (int) $quotation->signed_by_id === (int) $executive->id ? 'selected' : '' }}>
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
                            {{ (int) $quotation->division_id === (int) $division->id ? 'selected' : '' }}>
                            {{ $division->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Payment Accounts Selection --}}
    <div class="mb-3 p-3 border rounded bg-green-50">
        <label class="block text-text-primary font-semibold mb-2">
            Pilih Rekening Pembayaran <span class="text-error">*</span>
            <span class="text-xs font-normal text-text-label">(Minimal 1 rekening harus dipilih)</span>
        </label>
        <div class="space-y-2">
            @php
                $selectedAccounts = $quotation->selected_payment_accounts ?? [];
            @endphp
            @if ($paymentAccounts->count() > 0)
                @foreach ($paymentAccounts as $account)
                    <label
                        class="flex items-start p-2 bg-white rounded border hover:bg-surface-secondary cursor-pointer">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="mt-1 mr-3 payment-account-checkbox-edit"
                            onchange="validatePaymentSelectionEdit('{{ $quotation->quotation_number }}')"
                            {{ in_array((string) $account->id, array_map('strval', $selectedAccounts ?? [])) ? 'checked' : '' }}>
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
