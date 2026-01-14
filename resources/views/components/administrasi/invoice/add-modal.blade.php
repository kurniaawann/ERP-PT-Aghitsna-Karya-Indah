{{-- Modal Tambah Invoice --}}
<x-modal id="addModal" title="Tambah Invoice" action="{{ route('invoice.administrasi.store') }}" method="POST"
    buttonText="Simpan">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location" class="w-full border rounded p-2" placeholder="Jakarta (default)"
                maxlength="100" value="Jakarta">
            <small class="text-gray-500 text-xs">Contoh: Jakarta, Depok, Bogor</small>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="invoice_date" class="w-full border rounded p-2" required
                value="{{ date('Y-m-d') }}" oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada Yang Terhormat <span class="text-error">*</span></label>
        <input type="text" name="kepada" class="w-full border rounded p-2" placeholder="Nama penerima" required
            maxlength="255" oninvalid="this.setCustomValidity('Kepada tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Faktur No <span class="text-error">*</span></label>
            <input type="text" name="faktur_no" class="w-full border rounded p-2" placeholder="Masukkan faktur no"
                required maxlength="100" oninvalid="this.setCustomValidity('Faktur No tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">SJ.NO <span class="text-error">*</span></label>
            <input type="text" name="sj_no" class="w-full border rounded p-2" placeholder="Masukkan SJ No" required
                maxlength="100" oninvalid="this.setCustomValidity('SJ No tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>
    </div>

    {{-- Items Section --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-text-primary font-semibold">Daftar Barang <span class="text-error">*</span></label>
            <button type="button" onclick="addItemRow('addModal')"
                class="bg-primary hover:bg-primary-hover text-white px-3 py-1 rounded text-sm">
                <i class="fa-solid fa-plus mr-1"></i> Tambah Item
            </button>
        </div>

        <div id="itemsContainer-addModal" class="space-y-2">
            {{-- Initial item row --}}
            <div class="item-row border rounded p-3 bg-gray-50">
                <div class="grid grid-cols-12 gap-4 mb-2">
                    <div class="col-span-2">
                        <label class="block text-xs mb-1">Banyaknya</label>
                        <input type="number" name="item_banyaknya[]" class="w-full border rounded p-2 text-sm"
                            placeholder="Qty" min="1" required>
                    </div>
                    <div class="col-span-5">
                        <label class="block text-xs mb-1">Nama Barang</label>
                        <input type="text" name="item_nama_barang[]" class="w-full border rounded p-2 text-sm"
                            placeholder="Nama barang" required>
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs mb-1">Harga Satuan</label>
                        <input type="text" name="item_harga_satuan[]"
                            class="w-full border rounded p-2 text-sm price-input" placeholder="0" required>
                    </div>
                    <div class="col-span-2 flex items-end">
                        <button type="button" onclick="removeItemRow(this)"
                            class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-2 rounded text-sm">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Penerima s/d</label>
        <input type="text" name="penerima" class="w-full border rounded p-2" placeholder="Nama penerima"
            maxlength="255">
    </div>

    {{-- Optional Fields --}}
    <div class="border-t pt-3 mt-3">
        <label class="block text-text-primary font-semibold mb-2">Biaya Tambahan (Opsional)</label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-text-primary mb-1 text-sm">Sewa / Jual</label>
                <input type="text" name="sewa_jual" class="w-full border rounded p-2 price-input" placeholder="0">
            </div>

            <div>
                <label class="block text-text-primary mb-1 text-sm">Ongkos Kirim PP / 1x</label>
                <input type="text" name="ongkos_kirim" class="w-full border rounded p-2 price-input"
                    placeholder="0">
            </div>

            <div>
                <label class="block text-text-primary mb-1 text-sm">Bongkar / Pasang</label>
                <input type="text" name="bongkar_pasang" class="w-full border rounded p-2 price-input"
                    placeholder="0">
            </div>

            <div>
                <label class="block text-text-primary mb-1 text-sm">Lembur Antar / Ambil</label>
                <input type="text" name="lembur" class="w-full border rounded p-2 price-input" placeholder="0">
            </div>

            <div class="md:col-span-2">
                <label class="block text-text-primary mb-1 text-sm">Uang Jaminan</label>
                <input type="text" name="uang_jaminan" class="w-full border rounded p-2 price-input"
                    placeholder="0">
            </div>
        </div>
    </div>

    {{-- Bank Information --}}
    <div class="border-t pt-3 mt-3">
        <label class="block text-text-primary font-semibold mb-2">Informasi Bank</label>
        <p class="text-sm text-gray-600 mb-2">Pilih rekening bank yang akan ditampilkan di invoice:</p>

        <div class="space-y-2 max-h-40 overflow-y-auto border rounded p-2">
            @php
                $paymentAccounts = \App\Models\Invoice\PaymentAccount::where('is_active', true)->orderBy('id')->get();
            @endphp

            @if ($paymentAccounts->count() > 0)
                @foreach ($paymentAccounts as $account)
                    <label class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded cursor-pointer">
                        <input type="checkbox" name="selected_payment_accounts[]" value="{{ $account->id }}"
                            class="rounded border-gray-300">
                        <span class="text-sm">
                            <strong>{{ $account->bank_name }}</strong> -
                            {{ $account->account_number }}
                            (a/n {{ $account->account_holder }})
                        </span>
                    </label>
                @endforeach
            @else
                <p class="text-sm text-gray-500 italic">Belum ada data bank. Silakan tambahkan di menu pengaturan.</p>
            @endif
        </div>
    </div>

    {{-- PPN Section --}}
    <div class="border-t pt-3 mt-3">
        <label class="block text-text-primary font-semibold mb-2">PPN (Pajak Pertambahan Nilai)</label>

        <div class="flex items-center space-x-3">
            <div class="flex-1">
                <label class="block text-text-primary mb-1 text-sm">Persentase PPN (%)</label>
                <input type="number" name="ppn_percentage" class="w-full border rounded p-2" placeholder="12"
                    value="12" min="0" max="100" step="0.01">
                <small class="text-gray-500 text-xs">Default: 12%. Isi 0 jika tidak dikenakan PPN</small>
            </div>
        </div>
    </div>
</x-modal>
