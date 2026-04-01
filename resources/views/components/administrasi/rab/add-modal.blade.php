<x-modal id="addRABModal" title="Tambah RAB (Rancangan Anggaran Biaya)" action="{{ route('rab.store') }}" method="POST"
    buttonText="Simpan" formId="addRABForm">

    <input type="hidden" id="rabDataInput" name="rab_data" required>

    {{-- Tanggal --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
        <input type="date" class="w-full border rounded p-2" name="date" required value="{{ date('Y-m-d') }}"
            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Penerima --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Penerima <span class="text-error">*</span></label>
        <input type="text" class="w-full border rounded p-2" name="recipient" placeholder="Nama penerima RAB"
            required maxlength="255" oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Alamat Penerima --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat Penerima</label>
        <textarea class="w-full border rounded p-2" name="recipient_address" rows="2"
            placeholder="Masukkan alamat lengkap penerima" maxlength="500"></textarea>
        <small class="text-gray-500 text-xs">Maksimal 500 karakter</small>
    </div>

    {{-- Teks Pengantar --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Teks Pengantar <span class="text-error">*</span></label>
        <textarea class="w-full border rounded p-2" name="intro_text" rows="3"
            placeholder="Contoh: Bersama ini kami sampaikan perihal penawaran harga pekerjaan renovasi rumah tinggal 1 lantai, sebagai berikut:"
            required maxlength="1000" oninvalid="this.setCustomValidity('Teks pengantar tidak boleh kosong')"
            oninput="this.setCustomValidity('')"></textarea>
        <small class="text-gray-500 text-xs">Maksimal 1000 karakter</small>
    </div>

    {{-- Ditandatangani Oleh --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Ditandatangani Oleh</label>
        <input type="text" class="w-full border rounded p-2" name="signed_by" placeholder="Nama pejabat"
            maxlength="255">
    </div>

    {{-- Divisi --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Divisi/Bagian</label>
        <input type="text" class="w-full border rounded p-2" name="division" placeholder="Nama divisi"
            maxlength="255">
    </div>

    {{-- Rekening Pembayaran --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Rekening Pembayaran <span class="text-error">*</span></label>
        <div id="paymentAccountsList" class="space-y-2" required>
            <small class="text-gray-500 text-xs">Pilih minimal 1 rekening pembayaran</small>
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
        <div class="text-xs text-gray-600 mb-4 p-2 bg-gray-50 rounded">
            <p class="mb-1"><strong>Struktur:</strong> Kategori (Romawi) → Sub-Kategori (Angka) → Item (Huruf)</p>
            <p><strong>Contoh:</strong> I. Pekerjaan Persiapan → 1. Pembongkaran → a. Pembongkaran atap</p>
        </div>
    </div>

    <div id="rabCategoriesContainer" class="space-y-4 mb-3">
        <div class="category-block border rounded p-3 bg-white">
            <div class="mb-3">
                <label class="block text-text-primary mb-1 text-sm font-semibold">Kategori (Romawi)</label>
                <div class="flex gap-2">
                    <input type="text" class="flex-1 w-full border rounded p-2 category-name"
                        placeholder="Contoh: Pekerjaan Persiapan" required maxlength="255"
                        oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <button type="button" class="btn btn-sm btn-danger" title="Hapus kategori"
                        onclick="removeCategoryBlock(this)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="subcategories-container space-y-3 mb-3">
                <div class="subcategory-block border rounded p-3 bg-gray-50">
                    {{-- Sub-Kategori Info --}}
                    <div class="mb-3">
                        <label class="block text-text-primary mb-1 text-sm font-semibold">Sub-Kategori (Angka)</label>
                        <input type="text" class="w-full border rounded p-2 subcategory-name"
                            placeholder="Contoh: Pembongkaran" required maxlength="255"
                            oninvalid="this.setCustomValidity('Sub-kategori tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                    </div>

                    {{-- Volume, Satuan, Harga --}}
                    <div class="space-y-3 mb-3">
                        <div>
                            <label class="block text-text-primary mb-1 text-sm font-semibold">Volume</label>
                            <input type="number" class="w-full border rounded p-2 volume" placeholder="0"
                                min="0" step="0.01" required
                                oninvalid="this.setCustomValidity('Volume harus lebih dari 0')"
                                oninput="this.setCustomValidity('')">
                        </div>
                        <div>
                            <label class="block text-text-primary mb-1 text-sm font-semibold">Satuan</label>
                            <input type="text" class="w-full border rounded p-2 unit" placeholder="m²" required
                                maxlength="50" oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                                oninput="this.setCustomValidity('')">
                        </div>
                        <div>
                            <label class="block text-text-primary mb-1 text-sm font-semibold">Harga/Unit</label>
                            <input type="number" class="w-full border rounded p-2 unit-price" placeholder="0"
                                min="0" step="0.01" required
                                oninvalid="this.setCustomValidity('Harga satuan harus lebih dari 0')"
                                oninput="this.setCustomValidity('')">
                        </div>
                        <div class="bg-blue-50 border border-blue-300 rounded p-3 mb-3">
                            <p class="text-sm text-blue-900"><strong>Total Harga:</strong> <span
                                    class="sub-total-price font-bold text-lg text-blue-600">Rp 0</span></p>
                        </div>
                    </div>

                    {{-- Item Details --}}
                    <div class="mb-3">
                        <label class="block text-text-primary mb-2 text-sm font-semibold">Item Pekerjaan (a, b,
                            c...)</label>
                        <div class="items-container space-y-2">
                            <div class="item-block bg-white rounded border p-2 flex gap-2">
                                <input type="text" class="flex-1 w-full border-0 p-1 item-description"
                                    placeholder="Masukkan item pekerjaan" required maxlength="255"
                                    oninvalid="this.setCustomValidity('Item pekerjaan tidak boleh kosong')"
                                    oninput="this.setCustomValidity('')">
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

    {{-- Total Keseluruhan --}}
    <div class="flex justify-end mb-3">
        <div class="bg-green-50 border-2 border-green-300 rounded p-4 w-full md:w-80">
            <p class="text-sm text-green-900"><strong>Total Keseluruhan:</strong></p>
            <p class="font-bold text-2xl text-green-600"><span id="grandTotalPrice">Rp 0</span></p>
        </div>
    </div>

    <button type="button" onclick="addCategoryBlock('rabCategoriesContainer')"
        class="btn btn-outline-primary w-full">
        <i class="fa-solid fa-plus"></i> Tambah Kategori
    </button>

    <hr class="my-4">

    {{-- Biaya Lain-Lain Section --}}
    <div class="mb-3">
        <h6 class="text-text-primary font-semibold mb-3">III. Biaya Lain-Lain (Optional)</h6>
        <div class="text-xs text-gray-600 mb-3 p-2 bg-gray-50 rounded">
            <p><strong>Contoh:</strong> Iuran RT, Perizinan Air, Wifi/CCTV/AC, Pekerjaan Listrik, dll</p>
        </div>
    </div>

    <input type="hidden" id="miscCostsDataInput" name="misc_costs_data" value="[]">

    <div id="miscCostsContainer" class="space-y-2 mb-3">
        <!-- Items akan ditambah di sini -->
    </div>

    <button type="button" class="btn btn-sm btn-outline-secondary w-full mb-4" onclick="addMiscCostItem()">
        <i class="fa-solid fa-plus"></i> Tambah Biaya Lain-Lain
    </button>

</x-modal>
