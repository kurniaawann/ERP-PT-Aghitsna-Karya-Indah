{{-- =====================================================================
     Komponen Modal Tambah Penawaran Proyek (Project Quotation)

     Form tambah penawaran baru — hanya menyimpan kebutuhan PDF penawaran:
     - Nomor penawaran auto-generated (readonly)
     - Tanggal, Perihal, Kepada, Deskripsi Proyek
     - Daftar Item (flat, dinamis via JS)
     - Total & Discount (opsional)
     - Tanda Tangan (opsional), Rekening Pembayaran (wajib minimal 1)

     Separasi ketat: PPN & DP TIDAK ada di penawaran — keduanya diisi
     pada Invoice (modul Finance) via aksi "Buat Invoice".

     Data items dikirim via hidden field `items` (JSON) dengan format
     {keterangan, volume, satuan, harga} — sama seperti invoice.
     ===================================================================== --}}

<x-modal id="addModal" title="Tambah Penawaran Proyek"
    action="{{ route('project-quotation.store') }}" method="POST" buttonText="Simpan">

    {{-- Nomor Penawaran (readonly, auto-generated) --}}
    <div class="mb-3 bg-primary-light rounded p-3 flex items-center gap-3">
        <i class="fa-solid fa-hashtag text-primary"></i>
        <div>
            <p class="text-xs text-text-secondary">Nomor Penawaran (auto)</p>
            <p class="text-sm font-semibold text-primary" id="addQuotationNumberDisplay">
                Akan digenerate otomatis
            </p>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Lampiran</label>
            <input type="text" name="attachment" class="w-full border rounded p-2"
                placeholder="Contoh: 1 (satu) set gambar kerja">
        </div>
    @endif

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal penawaran tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Perihal (Hal)</label>
        <input type="text" name="subject" value="Penawaran Harga" class="w-full border rounded p-2"
            placeholder="Contoh: Penawaran Harga">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada Yth <span class="text-error">*</span></label>
        <input type="text" name="recipient" class="w-full border rounded p-2"
            placeholder="Nama penerima / perusahaan" required
            oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">{{ auth()->user()->isAdmin() ? 'Pembangunan untuk' : 'Deskripsi Proyek' }}</label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2"
            placeholder="Contoh: Proyek Karbela 3 / Pak Sis"></textarea>
    </div>

    @if (auth()->user()->role === 'superadmin')
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Nama Proyek</label>
            <input type="text" name="proyek" class="w-full border rounded p-2"
                placeholder="Contoh: Rumah Kost" oninput="this.setCustomValidity('')">
        </div>
    @endif

    @if (auth()->user()->isAdmin())
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location" class="w-full border rounded p-2"
                placeholder="Contoh: Jl. Karbela 3, Jakarta Selatan">
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Kota</label>
            <input type="text" name="city" class="w-full border rounded p-2"
                placeholder="Contoh: Jakarta (default)">
        </div>
    @endif

    <div id="items-container" class="mb-4">
        <div id="items-error"
            class="hidden mb-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span>Minimal harus ada 1 item dalam penawaran dengan data lengkap</span>
        </div>
        <label class="block text-text-primary font-semibold mb-2">Item-Item Penawaran <span
                class="text-error">*</span></label>
        <div id="items-list">
            <div class="item-row mb-3 p-3 border rounded bg-surface-secondary">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" class="item-keterangan border rounded p-2 w-full"
                        placeholder="Keterangan *" required
                        oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" class="item-volume border rounded p-2 w-full"
                        placeholder="Volume *" required oninput="calculateRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" class="item-satuan border rounded p-2 w-full"
                        placeholder="Satuan (m3, unit) *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" min="0"
                        class="item-harga border rounded p-2 w-full" placeholder="Harga *" required
                        oninput="formatCurrencyInput(this); calculateRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
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
            <span class="text-text-primary font-semibold">Total Penawaran:</span>
            <span id="invoice-total-preview" class="text-2xl font-bold text-primary">Rp 0</span>
        </div>
    </div>

    <!-- Discount Section -->
    <div class="mb-3 p-3 border rounded bg-yellow-50" id="discount-section">
        <label class="block text-text-primary font-semibold mb-2">Discount (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Tipe Discount</label>
                <select name="discount_type" id="discount-type" class="w-full border rounded p-2" disabled
                    onchange="calculateDiscount()">
                    <option value="">Tidak Ada Discount</option>
                    <option value="percentage">Persentase (%)</option>
                    <option value="amount">Nominal (Rp)</option>
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Nilai Discount</label>
                <input type="text" inputmode="decimal" name="discount_value" id="discount-value" disabled
                    class="w-full border rounded p-2 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    placeholder="0" oninput="formatDecimalInput(this); calculateDiscount()">
                <small class="text-xs text-text-secondary" id="discount-helper">Tidak boleh 100% atau lebih untuk
                    persentase. Boleh pakai koma, contoh 1,5</small>
                <div id="discount-error"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span id="discount-error-text">Persentase diskon tidak boleh 100% atau lebih</span>
                </div>
                <div id="discount-amount-error"
                    class="hidden mt-1 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-sm">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span id="discount-amount-error-text">Nominal diskon tidak boleh lebih dari atau sama dengan total
                        penawaran</span>
                </div>
            </div>
        </div>
        <div class="mt-2 p-2 bg-white rounded hidden" id="discount-summary">
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

    {{-- Signature Section (Opsional) --}}
    <div class="mb-3 p-3 border rounded bg-purple-50">
        <label class="block text-text-primary font-semibold mb-2">Tanda Tangan (Opsional)</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-text-label text-sm mb-1">Nama Penandatangan</label>
                <select name="signed_by_id" class="w-full border rounded p-2">
                    <option value="">-- Pilih Nama Penandatangan --</option>
                    @foreach ($executives as $executive)
                        <option value="{{ $executive->id }}">{{ $executive->name }} ({{ $executive->position }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-text-label text-sm mb-1">Divisi</label>
                <select name="division_id" class="w-full border rounded p-2">
                    <option value="">-- Pilih Divisi --</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
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
            @if (isset($paymentAccounts) && $paymentAccounts->count() > 0)
                @foreach ($paymentAccounts as $account)
                    <label
                        class="flex items-start p-2 bg-white rounded border hover:bg-surface-secondary cursor-pointer">
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
</x-modal>
