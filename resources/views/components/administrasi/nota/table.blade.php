{{-- =====================================================================
     Tabel Daftar Nota
     PT Aghitsna Karya Indah

     Komponen tabel untuk menampilkan daftar nota.
     Setiap baris memiliki checkbox untuk seleksi massal.

     Kolom:
     - Checkbox (select all)
     - No. Nota (kode unik)
     - Kepada (penerima)
     - Faktur No
     - SJ No
     - Jumlah Total (format Rupiah)
     - Tanggal
     - Aksi (Edit)
     ===================================================================== --}}

{{-- Tabel Daftar Nota --}}
<form id="deleteForm" method="POST" action="{{ route('nota.administrasi.destroySelected') }}">
    @csrf
    @method('DELETE')

    {{-- Tabel Utama --}}
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">

                    {{-- Header Tabel --}}
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No. Nota</th>
                            <th class="p-2 text-left">Kepada</th>
                            <th class="p-2 text-left">Faktur No</th>
                            <th class="p-2 text-left">SJ No</th>
                            <th class="p-2 text-right">Jumlah Total</th>
                            <th class="p-2 text-center">Tanggal</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>

                    {{-- Body Tabel --}}
                    <tbody>
                        @forelse($notas as $nota)
                            <tr class="border-t hover:bg-surface-secondary">

                                {{-- Checkbox --}}
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $nota->id_nota }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                {{-- No. Nota --}}
                                <td class="p-2 font-medium text-primary">{{ $nota->id_nota }}</td>

                                {{-- Kepada --}}
                                <td class="p-2">{{ $nota->kepada }}</td>

                                {{-- Faktur No --}}
                                <td class="p-2">{{ $nota->faktur_no }}</td>

                                {{-- SJ No --}}
                                <td class="p-2">{{ $nota->sj_no }}</td>

                                {{-- Jumlah Total (format Rupiah) --}}
                                <td class="p-2 text-right font-semibold text-success">
                                    Rp {{ number_format($nota->jumlah_total, 0, ',', '.') }}
                                </td>

                                {{-- Tanggal (format: dd/mm/yyyy) --}}
                                <td class="p-2 text-center">
                                    {{ \Carbon\Carbon::parse($nota->nota_date)->format('d/m/Y') }}
                                </td>

                                {{-- Aksi --}}
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- Tombol Edit --}}
                                        <button type="button" onclick="openModal('editModal-{{ $nota->id_nota }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Nota">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            {{-- Pesan jika data kosong --}}
                            <tr>
                                <td colspan="8" class="text-center p-4 text-text-secondary">
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
