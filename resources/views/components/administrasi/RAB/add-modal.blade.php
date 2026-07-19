{{--
    Component Modal Tambah RAB
    Form untuk menambahkan RAB baru dengan struktur hierarki: Kategori -> Sub-Kategori -> Item.

    Data:
    - $paymentAccounts: Collection dari PaymentAccount aktif
--}}
<x-modal id="addRABModal" title="Tambah RAB (Rancangan Anggaran Biaya)" action="{{ route('rab.store') }}" method="POST"
    buttonText="Simpan" formId="addRABForm">

    <input type="hidden" id="rabDataInput" name="rab_data" required>

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="date" required value="{{ date('Y-m-d') }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Penerima --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Penerima <span class="text-error">*</span></label>
        <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="recipient" placeholder="Nama penerima RAB" required maxlength="255"
            oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Alamat Penerima --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat Penerima</label>
        <textarea class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="recipient_address" rows="2" placeholder="Masukkan alamat lengkap penerima" maxlength="500"></textarea>
        <small class="text-text-secondary text-xs">Maksimal 500 karakter</small>
    </div>

    {{-- Teks Pengantar --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Teks Pengantar <span class="text-error">*</span></label>
        <textarea class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" name="intro_text"
            rows="3"
            placeholder="Contoh: Bersama ini kami sampaikan perihal penawaran harga pekerjaan renovasi rumah tinggal 1 lantai, sebagai berikut:"
            required maxlength="1000" oninvalid="this.setCustomValidity('Teks pengantar tidak boleh kosong')"
            oninput="this.setCustomValidity('')"></textarea>
        <small class="text-text-secondary text-xs">Maksimal 1000 karakter</small>
    </div>

    {{-- Ditandatangani Oleh --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Ditandatangani Oleh</label>
        <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="signed_by" placeholder="Nama pejabat" maxlength="255">
    </div>

    {{-- Divisi --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Divisi/Bagian</label>
        <input type="text" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            name="division" placeholder="Nama divisi" maxlength="255">
    </div>

    {{-- Rekening Pembayaran --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Rekening Pembayaran <span class="text-error">*</span></label>
        <div id="paymentAccountsList" class="space-y-2" required>
            <small class="text-text-secondary text-xs">Pilih minimal 1 rekening pembayaran</small>
            @foreach ($paymentAccounts as $account)
                <div class="flex items-center">
                    <input class="form-check-input" type="checkbox" name="selected_payment_accounts[]"
                        value="{{ $account->id }}" id="paymentAccount{{ $account->id }}">
                    <label class="form-check-label ml-2 cursor-pointer text-sm" for="paymentAccount{{ $account->id }}">
                        <span class="font-medium">{{ $account->bank_name }}</span> - {{ $account->account_number }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <hr class="my-4">

    {{-- Detail Pekerjaan --}}
    <div class="mb-3">
        <h6 class="text-text-primary font-semibold mb-3">Detail Pekerjaan (Struktur Hierarki)</h6>
        <div class="text-xs text-text-secondary mb-4 p-2 bg-surface-secondary rounded">
            <p class="mb-1"><strong>Struktur:</strong> Kategori (Romawi) → Sub-Kategori (Angka) → Item (Huruf)</p>
            <p><strong>Catatan:</strong> Volume, satuan, harga satuan, dan sub-harga diisi pada item huruf a, b, c...</p>
        </div>
    </div>

    {{-- Container Kategori --}}
    <div id="rabCategoriesContainer" class="space-y-4 mb-3">
        <div class="category-block border border-border-strong rounded p-3 bg-surface-base">
            <div class="mb-3">
                <label class="block text-text-primary mb-1 text-sm font-semibold">Kategori (Romawi)</label>
                <div class="flex gap-2">
                    <input type="text"
                        class="flex-1 w-full border border-border-strong rounded p-2 bg-surface-base text-text-input category-name"
                        placeholder="Contoh: Pekerjaan Persiapan" required maxlength="255"
                        oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')" oninput="this.setCustomValidity('')">
                    <button type="button" class="btn btn-sm btn-danger" title="Hapus kategori"
                        onclick="removeCategoryBlock(this)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="subcategories-container space-y-3 mb-3">
                <div class="subcategory-block border border-border-strong rounded p-3 bg-surface-secondary">
                    <div class="mb-3">
                        <label class="block text-text-primary mb-1 text-sm font-semibold">Sub-Kategori (Angka)</label>
                        <input type="text"
                            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input subcategory-name"
                            placeholder="Contoh: Pembongkaran" required maxlength="255"
                            oninvalid="this.setCustomValidity('Sub-kategori tidak boleh kosong')" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="mb-3">
                        <label class="block text-text-primary mb-2 text-sm font-semibold">Item Pekerjaan (a, b, c...)</label>
                        <div class="items-container space-y-2">
                            <div class="item-block bg-surface-base rounded border border-border-strong p-3 flex flex-col gap-2">
                                <input type="text"
                                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input item-description"
                                    placeholder="Masukkan item pekerjaan" required maxlength="255"
                                    oninvalid="this.setCustomValidity('Item pekerjaan tidak boleh kosong')" oninput="this.setCustomValidity('')">
                                <input type="number"
                                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input item-volume"
                                    placeholder="Vol" min="0" step="0.01" required
                                    oninput="updatePrices()">
                                <input type="text"
                                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input item-unit"
                                    placeholder="Satuan" maxlength="50" required oninput="updatePrices()">
                                <input type="number"
                                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input item-unit-price"
                                    placeholder="Harga" min="0" step="0.01" required
                                    oninput="updatePrices()">
                                <div class="w-full px-3 py-2 bg-primary-light border border-primary rounded text-right">
                                    <span class="item-sub-total-price text-sm font-semibold text-primary">Rp 0</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeItemBlock(this)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-full mt-2"
                            onclick="addItemBlock(this)">
                            <i class="fa-solid fa-plus"></i> Tambah Item
                        </button>
                    </div>

                    <div class="bg-primary-light border border-primary rounded p-3 mb-3">
                        <p class="text-sm text-primary"><strong>Total Sub-Kategori:</strong> <span
                                class="sub-total-price font-bold text-lg text-primary">Rp 0</span></p>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-danger w-full"
                        onclick="removeSubcategoryBlock(this)">
                        <i class="fa-solid fa-trash"></i> Hapus Sub-Kategori
                    </button>
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary w-full"
                onclick="addSubcategoryBlock(this)">
                <i class="fa-solid fa-plus"></i> Tambah Sub-Kategori
            </button>
        </div>
    </div>

    <button type="button" onclick="addCategoryBlock('rabCategoriesContainer')"
        class="btn btn-outline-primary w-full">
        <i class="fa-solid fa-plus"></i> Tambah Kategori
    </button>

    <hr class="my-4">

    {{-- Biaya Lain-Lain --}}
    <div class="mb-3">
        <h6 class="text-text-primary font-semibold mb-3">III. Biaya Lain-Lain (Optional)</h6>
        <div class="text-xs text-text-secondary mb-3 p-2 bg-surface-secondary rounded">
            <p><strong>Contoh:</strong> Iuran RT, Perizinan Air, Wifi/CCTV/AC, Pekerjaan Listrik, dll</p>
        </div>
    </div>

    <input type="hidden" id="miscCostsDataInput" name="misc_costs_data" value="[]">

    <div id="miscCostsContainer" class="space-y-2 mb-3"></div>

    <button type="button" class="btn btn-sm btn-outline-secondary w-full mb-4" onclick="addMiscCostItem()">
        <i class="fa-solid fa-plus"></i> Tambah Biaya Lain-Lain
    </button>

    <hr class="my-4">

    {{-- Total Keseluruhan --}}
    <div class="flex justify-end mb-3">
        <div class="bg-success-light border-2 border-success rounded p-4 w-full">
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-success border-b border-success pb-2">
                    <span><strong>Total Kategori:</strong></span>
                    <span id="totalCategoriesPrice" class="font-semibold">Rp 0</span>
                </div>
                <div class="flex justify-between text-sm text-success border-b border-success pb-2">
                    <span><strong>Total Biaya Lain-Lain:</strong></span>
                    <span id="totalMiscCostsPrice" class="font-semibold">Rp 0</span>
                </div>
                <div class="flex justify-between text-lg text-success">
                    <span><strong>Total Keseluruhan:</strong></span>
                    <p class="font-bold text-2xl text-success"><span id="grandTotalPrice">Rp 0</span></p>
                </div>
            </div>
        </div>
    </div>

</x-modal>
