{{-- =====================================================================
     Komponen Tabel Surat Perintah Kerja (SPK)

     Menampilkan daftar SPK dalam bentuk tabel dengan:
     - Checkbox untuk pemilihan massal
     - Kolom: No., Nomor, Proyek, Lokasi, Pemberi Tugas, Total, Tanggal, Aksi
     - Form hapus massal yang membungkus seluruh tabel
     ===================================================================== --}}

<form id="deleteForm" method="POST" action="{{ route('surat-perintah-kerja.administrasi.destroySelected') }}">
    @csrf
    @method('DELETE')

    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">Nomor SPK</th>
                            <th class="p-2 text-left">Proyek</th>
                            <th class="p-2 text-left">Lokasi</th>
                            <th class="p-2 text-left">Pemberi Tugas</th>
                            <th class="p-2 text-right">Total</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($suratPerintahKerjas as $spk)
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $spk->nomor }}"
                                        class="row-checkbox w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $spk->nomor }}</td>

                                <td class="p-2">{{ $spk->proyek }}</td>

                                <td class="p-2">{{ $spk->lokasi }}</td>

                                <td class="p-2">{{ $spk->pemberi_tugas_nama }}</td>

                                <td class="p-2 text-right font-semibold">
                                    {{ number_format($spk->total_amount, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($spk->tanggal)->format('d/m/Y') }}
                                </td>

                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                            onclick="showDetailModal('{{ $spk->nomor }}')"
                                            class="flex items-center gap-1 bg-primary hover:bg-primary-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Lihat Detail SPK">
                                            <i class="fa-solid fa-eye"></i>
                                            <span>Detail</span>
                                        </button>
                                        <button type="button"
                                            onclick="openModal('editModal-{{ $spk->nomor }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit SPK">
                                            <i class="fa-solid fa-pencil"></i>
                                            <span>Edit</span>
                                        </button>
                                        <a href="{{ route('surat-perintah-kerja.administrasi.export.pdf', $spk->nomor) }}"
                                            class="flex items-center gap-1 bg-error hover:bg-error/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Download PDF">
                                            <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                            <span>PDF</span>
                                        </a>
                                        <a href="{{ route('surat-perintah-kerja.administrasi.export.word', $spk->nomor) }}"
                                            class="flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Download Word">
                                            <i class="fa-solid fa-file-word w-3 h-3"></i>
                                            <span>Word</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-500">
                                    <i class="fa-solid fa-inbox text-2xl mb-2 block opacity-50"></i>
                                    Tidak ada surat perintah kerja ditemukan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
