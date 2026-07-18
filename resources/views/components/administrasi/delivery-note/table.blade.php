{{-- =====================================================================
     Komponen Tabel Surat Jalan (Delivery Note)

     Menampilkan daftar surat jalan dalam bentuk tabel dengan:
     - Checkbox untuk pemilihan massal
     - Kolom: No. Dokumen, Penerima, Pengirim, Total Jumlah, Tanggal, Aksi
     - Form hapus massal yang membungkus seluruh tabel
     ===================================================================== --}}

{{-- Form hapus massal: dibungkus di seluruh tabel --}}
<form id="deleteForm" method="POST" action="{{ route('delivery-note.administrasi.destroySelected') }}">
    @csrf
    @method('DELETE')

    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    {{-- Header Tabel --}}
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No. Dokumen</th>
                            <th class="p-2 text-left">Penerima</th>
                            <th class="p-2 text-left">Pengirim</th>
                            <th class="p-2 text-center">Total Jumlah</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>

                    {{-- Body Tabel --}}
                    <tbody>
                        @forelse($deliveryNotes as $deliveryNote)
                            <tr class="border-t hover:bg-surface-secondary">
                                {{-- Checkbox Pilihan --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $deliveryNote->id_delivery_note }}"
                                        class="row-checkbox w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                {{-- No. Dokumen --}}
                                <td class="p-2 font-medium text-primary">{{ $deliveryNote->id_delivery_note }}</td>

                                {{-- Nama Penerima --}}
                                <td class="p-2">{{ $deliveryNote->receiver_name }}</td>

                                {{-- Nama Pengirim --}}
                                <td class="p-2">{{ $deliveryNote->shipper_name }}</td>

                                {{-- Total Jumlah Barang --}}
                                <td class="p-2 text-center font-semibold">
                                    {{ $deliveryNote->total_quantity }}
                                </td>

                                {{-- Tanggal Pengiriman --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($deliveryNote->delivery_date)->format('d/m/Y') }}
                                </td>

                                {{-- Tombol Aksi: Detail, Edit --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                            onclick="showDetailModal('{{ $deliveryNote->id_delivery_note }}')"
                                            class="flex items-center gap-1 bg-primary hover:bg-primary-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Lihat Detail Surat Jalan">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>Detail</span>
                                        </button>
                                        <button type="button"
                                            onclick="openModal('editModal-{{ $deliveryNote->id_delivery_note }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Surat Jalan">
                                            <i class="fa-solid fa-pencil"></i>
                                            <span>Edit</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            {{-- Tampilan saat data kosong --}}
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-500">
                                    <i class="fa-solid fa-inbox text-2xl mb-2 block opacity-50"></i>
                                    Tidak ada surat jalan ditemukan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
