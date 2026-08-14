{{-- Modal Tambah DO Semen (header + baris Data Semen) --}}
<x-modal id="addModal" title="Tambah DO Semen" action="{{ route('cement-do.store') }}" method="POST"
    buttonText="Simpan" size="6xl">

    {{-- Info DO (header) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-b pb-3 mb-3">

        {{-- Field: Tanggal DO --}}
        <div>
            <label class="block text-text-primary mb-1">Tanggal DO <span class="text-error">*</span></label>
            <input type="date" name="tanggal" class="w-full border rounded p-2" required
                oninvalid="this.setCustomValidity('Tanggal DO tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        {{-- Field: Tanggal Datang --}}
        <div>
            <label class="block text-text-primary mb-1">Tanggal Datang</label>
            <input type="date" name="tanggal_datang" class="w-full border rounded p-2">
        </div>

        {{-- Field: Tanggal Bayar --}}
        <div>
            <label class="block text-text-primary mb-1">Tanggal Bayar</label>
            <input type="date" name="tanggal_bayar" class="w-full border rounded p-2">
        </div>

        {{-- Field: Harga Modal (per DO) --}}
        <div>
            <label class="block text-text-primary mb-1">Harga Modal <span class="text-error">*</span></label>
            <input type="text" name="harga_modal" value="Rp 0" class="w-full border rounded p-2" placeholder="Rp 0"
                required inputmode="numeric" id="add-harga-modal"
                oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <p class="text-xs text-text-secondary mt-1">Total modal untuk seluruh baris semen dalam DO ini.</p>
        </div>
    </div>

    {{-- Baris Data Semen (detail) --}}
    <div class="mb-3">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-text-primary font-medium">Data Semen <span class="text-error">*</span></label>
            <button type="button" id="add-row-btn"
                class="flex items-center gap-1 bg-btn-add hover:bg-btn-add-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs">
                <i class="fa-solid fa-plus w-3 h-3"></i> Tambah Baris
            </button>
        </div>

        <div class="border rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="text-xs text-text-secondary">
                        <th class="p-2 text-left">Tanggal</th>
                        <th class="p-2 text-left">Nama Proyek</th>
                        <th class="p-2 text-left">Nama</th>
                        <th class="p-2 text-left">Rekening Pembayaran</th>
                        <th class="p-2 text-center">Jumlah (zak)</th>
                        <th class="p-2 text-right">Harga</th>
                        <th class="p-2 text-left">Tgl Lunas</th>
                        <th class="p-2 text-center"></th>
                    </tr>
                </thead>
                <tbody id="cement-rows" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>

        <p class="text-xs text-text-secondary mt-1">Satu DO dapat memuat banyak baris data semen. Klik "Tambah Baris"
            untuk menambahkan.</p>
    </div>

    {{-- Template baris semen (disalin oleh JS) --}}
    <template id="cement-row-template">
        <tr class="cement-row">
            <td class="p-1">
                <input type="date" name="cements[IDX][tanggal]" class="w-full border rounded p-2 text-sm">
            </td>
            <td class="p-1">
                <input type="text" name="cements[IDX][nama_proyek]"
                    class="w-full border rounded p-2 text-sm" placeholder="Nama proyek"
                    required maxlength="255"
                    oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </td>
            <td class="p-1">
                <input type="text" name="cements[IDX][name]"
                    class="w-full border rounded p-2 text-sm" placeholder="Nama pemesan"
                    maxlength="255">
            </td>
            <td class="p-1">
                <select name="cements[IDX][payment_account_id]"
                    class="w-full border rounded p-2 text-sm">
                    <option value="">-- Pilih Rekening --</option>
                    @foreach ($paymentAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->bank_name }} -
                            {{ $account->account_number }}</option>
                    @endforeach
                </select>
            </td>
            <td class="p-1">
                <input type="number" name="cements[IDX][jumlah]" value="0" min="0"
                    class="w-full border rounded p-2 text-sm text-center" placeholder="0" required>
            </td>
            <td class="p-1">
                <input type="text" name="cements[IDX][harga]" value="Rp 0"
                    class="w-full border rounded p-2 text-sm text-right cement-harga" placeholder="Rp 0"
                    required inputmode="numeric">
            </td>
            <td class="p-1">
                <input type="date" name="cements[IDX][tanggal_lunas]" class="w-full border rounded p-2 text-sm">
            </td>
            <td class="p-1 text-center">
                <button type="button"
                    class="remove-row-btn text-error hover:text-red-700 px-2 py-1 rounded"
                    title="Hapus baris">
                    <i class="fa-solid fa-trash w-3 h-3"></i>
                </button>
            </td>
        </tr>
    </template>
</x-modal>
