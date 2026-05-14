{{-- Modal Tambah Invoice Proyek --}}
<x-modal id="addModal" title="Tambah Invoice Proyek" action="{{ route('proyek-invoice.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada (Nama Penerima) <span class="text-error">*</span></label>
        <input type="text" name="recipient" class="w-full border rounded p-2" placeholder="Nama penerima invoice"
            required oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Hal / Regarding <span class="text-error">*</span></label>
        <input type="text" name="regarding" class="w-full border rounded p-2" placeholder="Contoh: Pengajuan Dana"
            required oninvalid="this.setCustomValidity('Hal/Regarding tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Deskripsi Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2"
            placeholder="Contoh: Renovasi Rumah" required
            oninvalid="this.setCustomValidity('Deskripsi proyek tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>

    <div id="items-container" class="mb-4">
        <label class="block text-text-primary font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="items-list">
            <div class="item-row mb-3 p-3 border rounded bg-surface-secondary">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *"
                        required oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" class="item-volume border rounded p-2 w-full"
                        placeholder="Volume *" required oninput="calculateRowTotal(this)"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')"
                        oninput="calculateRowTotal(this); this.setCustomValidity('')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" class="item-satuan border rounded p-2 w-full"
                        placeholder="Satuan (m3, unit) *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" class="item-harga border rounded p-2 w-full"
                        placeholder="Harga *" required oninput="calculateRowTotal(this)"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')"
                        oninput="calculateRowTotal(this); this.setCustomValidity('')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <button type="button"
                        class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <button type="button" id="add-item" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    <!-- Live Total Preview -->
    <div class="mb-4 p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-text-primary font-semibold">Total Invoice:</span>
            <span id="invoice-total-preview" class="text-2xl font-bold text-primary">Rp 0</span>
        </div>
        <div class="text-xs text-text-secondary mt-1" id="invoice-total-words"></div>
    </div>

    <!-- Discount Section -->
    <div class="mb-3 p-3 border rounded bg-yellow-50">
        <label class="block text-text-primary font-semibold mb-2">Discount (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Tipe Discount</label>
                <select name="discount_type" id="discount-type" class="w-full border rounded p-2"
                    onchange="calculateDiscount()">
                    <option value="">Tidak Ada Discount</option>
                    <option value="percentage">Persentase (%)</option>
                    <option value="amount">Nominal (Rp)</option>
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Nilai Discount</label>
                <input type="number" step="0.01" min="0" name="discount_value" id="discount-value"
                    class="w-full border rounded p-2 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    placeholder="Pilih tipe dulu" disabled oninput="calculateDiscount()">
                <small class="text-xs text-text-secondary" id="discount-helper">Maksimal 100% untuk persentase</small>
                <div id="discount-error"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span id="discount-error-text">Persentase diskon tidak boleh lebih dari 100%</span>
                </div>
            </div>
        </div>
        <div class="mt-2 p-2 bg-white rounded">
            <div class="flex justify-between">
                <span class="text-sm text-text-label">Discount:</span>
                <span id="discount-amount" class="text-sm font-semibold text-red-600">Rp 0</span>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-sm font-bold text-text-primary">Total Setelah Discount:</span>
                <span id="total-after-discount" class="text-sm font-bold text-green-600">Rp 0</span>
            </div>
        </div>
    </div>

    <!-- DP Section -->
    <div class="mb-3 p-3 border rounded bg-blue-50">
        <label class="block text-text-primary font-semibold mb-2">DP / Uang Muka (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Tipe DP</label>
                <select name="dp_type" id="dp-type" class="w-full border rounded p-2" onchange="calculateDP()">
                    <option value="">Tidak Ada DP</option>
                    <option value="percentage">Persentase (%)</option>
                    <option value="amount">Nominal (Rp)</option>
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Nilai DP</label>
                <input type="number" step="0.01" min="0" name="dp_value" id="dp-value"
                    class="w-full border rounded p-2 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    placeholder="Pilih tipe dulu" disabled oninput="calculateDP()">
                <small class="text-xs text-text-secondary" id="dp-helper">Maksimal 100% untuk persentase</small>
                <div id="dp-error"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span id="dp-error-text">Persentase DP tidak boleh lebih dari 100%</span>
                </div>
            </div>
        </div>
        <div class="mt-2 p-2 bg-white rounded">
            <div class="flex justify-between">
                <span class="text-sm font-bold text-text-primary">Nilai DP:</span>
                <span id="dp-amount" class="text-sm font-bold text-blue-600">Rp 0</span>
            </div>
        </div>
    </div>

    <!-- Payment Installments Section -->
    <div class="mb-3 p-3 border rounded bg-purple-50">
        <label class="block text-text-primary font-semibold mb-2">
            Pembayaran Bertahap (Opsional)
            <span class="text-xs font-normal text-text-label">- Contoh: Pembayaran Ke 1, Ke 2, Sisa</span>
        </label>
        <p class="text-xs text-text-label mb-3">
            <i class="fa-solid fa-info-circle"></i>
            Tambahkan detail pembayaran jika invoice ini dibayar secara bertahap
        </p>
        <div id="payment-installments-list" class="space-y-2">
            <!-- Installments will be added here dynamically -->
        </div>
        <button type="button" id="add-payment-installment"
            class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 mt-2">
            <i class="fa-solid fa-plus"></i> Tambah Pembayaran
        </button>
    </div> <!-- Payment Accounts Selection -->
    <div class="mb-3 p-3 border rounded bg-green-50">
        <label class="block text-text-primary font-semibold mb-2">
            Pilih Rekening Pembayaran <span class="text-error">*</span>
            <span class="text-xs font-normal text-text-label">(Minimal 1 rekening harus dipilih)</span>
        </label>
        <div class="space-y-2">
            @if (isset($paymentAccounts) && $paymentAccounts->count() > 0)
                @foreach ($paymentAccounts as $account)
                    <label class="flex items-start p-2 bg-white rounded border hover:bg-surface-secondary cursor-pointer">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="mt-1 mr-3 payment-account-checkbox" onchange="validatePaymentSelection()">
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
        <div id="payment-account-error" class="text-red-600 text-sm mt-2 hidden">
            <i class="fa-solid fa-exclamation-circle"></i> Minimal 1 rekening harus dipilih
        </div>
    </div>

    <input type="hidden" name="items" id="items-json" value="[]">
    <input type="hidden" name="payment_installments" id="payment-installments-json" value="[]">
</x-modal>
