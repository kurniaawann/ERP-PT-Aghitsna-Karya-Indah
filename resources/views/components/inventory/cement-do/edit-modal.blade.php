{{-- Modal Edit DO Semen (header + baris Data Semen) --}}
<x-modal id="editModal-{{ $cementDeliveryOrder->no }}" title="Edit DO Semen"
    action="{{ route('cement-do.update', $cementDeliveryOrder->no) }}" method="PUT" buttonText="Update" size="6xl">

    {{-- Field: No (Readonly) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">No</label>
        <input type="text" value="{{ $cementDeliveryOrder->no }}"
            class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed" readonly>
        <p class="text-xs text-text-secondary mt-1">No tidak dapat diubah</p>
    </div>

    {{-- Info DO (header) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-b pb-3 mb-3">

        {{-- Field: Tanggal DO --}}
        <div>
            <label class="block text-text-primary mb-1">Tanggal DO <span class="text-error">*</span></label>
            <input type="date" name="tanggal" value="{{ $cementDeliveryOrder->tanggal?->format('Y-m-d') }}"
                class="w-full border rounded p-2" required
                oninvalid="this.setCustomValidity('Tanggal DO tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        {{-- Field: Tanggal Datang --}}
        <div>
            <label class="block text-text-primary mb-1">Tanggal Datang</label>
            <input type="date" name="tanggal_datang"
                value="{{ $cementDeliveryOrder->tanggal_datang?->format('Y-m-d') }}"
                class="w-full border rounded p-2">
        </div>

        {{-- Field: Tanggal Bayar --}}
        <div>
            <label class="block text-text-primary mb-1">Tanggal Bayar</label>
            <input type="date" name="tanggal_bayar" value="{{ $cementDeliveryOrder->tanggal_bayar?->format('Y-m-d') }}"
                class="w-full border rounded p-2">
        </div>

        {{-- Field: Harga Modal (per DO) --}}
        <div>
            <label class="block text-text-primary mb-1">Harga Modal <span class="text-error">*</span></label>
            <input type="text" name="harga_modal"
                value="Rp {{ number_format($cementDeliveryOrder->harga_modal ?? 0, 0, ',', '.') }}"
                class="w-full border rounded p-2" required inputmode="numeric"
                id="edit-harga-modal-{{ $cementDeliveryOrder->no }}"
                oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <p class="text-xs text-text-secondary mt-1">Total modal untuk seluruh baris semen dalam DO ini.</p>
        </div>
    </div>

    {{-- Baris Data Semen (detail) --}}
    <div class="mb-3">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-text-primary font-medium">Data Semen <span class="text-error">*</span></label>
            <button type="button"
                class="add-row-btn flex items-center gap-1 bg-btn-add hover:bg-btn-add-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs">
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
                        <th class="p-2 text-center">Jumlah (zak)</th>
                        <th class="p-2 text-right">Harga</th>
                        <th class="p-2 text-left">Tgl Lunas</th>
                        <th class="p-2 text-center"></th>
                    </tr>
                </thead>
                <tbody class="cement-rows divide-y divide-gray-100">
                    @forelse ($cementDeliveryOrder->cements as $index => $cement)
                        <tr class="cement-row">
                            <td class="p-1">
                                <input type="date" name="cements[{{ $index }}][tanggal]"
                                    value="{{ $cement->tanggal?->format('Y-m-d') }}" class="w-full border rounded p-2 text-sm">
                            </td>
                            <td class="p-1">
                                <input type="text" name="cements[{{ $index }}][nama_proyek]"
                                    value="{{ $cement->nama_proyek }}" class="w-full border rounded p-2 text-sm"
                                    placeholder="Nama proyek" required maxlength="255"
                                    oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
                                    oninput="this.setCustomValidity('')">
                            </td>
                            <td class="p-1">
                                <input type="text" name="cements[{{ $index }}][name]"
                                    value="{{ $cement->name }}" class="w-full border rounded p-2 text-sm"
                                    placeholder="Nama pemesan" maxlength="255">
                            </td>
                            <td class="p-1">
                                <input type="number" name="cements[{{ $index }}][jumlah]"
                                    value="{{ $cement->jumlah }}" min="0"
                                    class="w-full border rounded p-2 text-sm text-center" placeholder="0" required>
                            </td>
                            <td class="p-1">
                                <input type="text" name="cements[{{ $index }}][harga]"
                                    value="Rp {{ number_format($cement->harga ?? 0, 0, ',', '.') }}"
                                    class="w-full border rounded p-2 text-sm text-right cement-harga"
                                    placeholder="Rp 0" required inputmode="numeric">
                            </td>
                            <td class="p-1">
                                <input type="date" name="cements[{{ $index }}][tanggal_lunas]"
                                    value="{{ $cement->tanggal_lunas?->format('Y-m-d') }}"
                                    class="w-full border rounded p-2 text-sm">
                            </td>
                            <td class="p-1 text-center">
                                <button type="button"
                                    class="remove-row-btn text-error hover:text-red-700 px-2 py-1 rounded"
                                    title="Hapus baris">
                                    <i class="fa-solid fa-trash w-3 h-3"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="cement-row">
                            <td class="p-1">
                                <input type="date" name="cements[0][tanggal]" class="w-full border rounded p-2 text-sm">
                            </td>
                            <td class="p-1">
                                <input type="text" name="cements[0][nama_proyek]"
                                    class="w-full border rounded p-2 text-sm" placeholder="Nama proyek" required
                                    maxlength="255"
                                    oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')"
                                    oninput="this.setCustomValidity('')">
                            </td>
                            <td class="p-1">
                                <input type="text" name="cements[0][name]"
                                    class="w-full border rounded p-2 text-sm" placeholder="Nama pemesan"
                                    maxlength="255">
                            </td>
                            <td class="p-1">
                                <input type="number" name="cements[0][jumlah]" value="0" min="0"
                                    class="w-full border rounded p-2 text-sm text-center" placeholder="0" required>
                            </td>
                            <td class="p-1">
                                <input type="text" name="cements[0][harga]" value="Rp 0"
                                    class="w-full border rounded p-2 text-sm text-right cement-harga"
                                    placeholder="Rp 0" required inputmode="numeric">
                            </td>
                            <td class="p-1">
                                <input type="date" name="cements[0][tanggal_lunas]" class="w-full border rounded p-2 text-sm">
                            </td>
                            <td class="p-1 text-center">
                                <button type="button"
                                    class="remove-row-btn text-error hover:text-red-700 px-2 py-1 rounded"
                                    title="Hapus baris">
                                    <i class="fa-solid fa-trash w-3 h-3"></i>
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-xs text-text-secondary mt-1">Satu DO dapat memuat banyak baris data semen. Klik "Tambah Baris"
            untuk menambahkan.</p>
    </div>
</x-modal>
