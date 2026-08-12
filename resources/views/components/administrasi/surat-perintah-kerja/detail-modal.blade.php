{{-- =====================================================================
     Komponen Modal Detail Surat Perintah Kerja (SPK)

     Menampilkan data SPK secara lengkap dalam mode read-only.
     Satu modal per data SPK.
     ===================================================================== --}}

<x-modal id="detailModal-{{ $spk->nomor }}" title="Detail Surat Perintah Kerja" :readonly="true" size="4xl">

    {{-- Info Dokumen --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">Nomor SPK</p>
            <p class="text-sm font-medium text-primary">{{ $spk->nomor }}</p>
        </div>
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">Proyek</p>
            <p class="text-sm font-medium text-text-primary">{{ $spk->proyek }}</p>
        </div>
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">Lokasi</p>
            <p class="text-sm font-medium text-text-primary">{{ $spk->lokasi }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">Tanggal</p>
            <p class="text-sm font-medium text-text-primary">{{ \Carbon\Carbon::parse($spk->tanggal)->format('d/m/Y') }}</p>
        </div>
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">Total Pekerjaan</p>
            <p class="text-sm font-bold text-primary">Rp {{ number_format($spk->total_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Pemberi Tugas & Penandatangan --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
        <div class="border border-border-strong rounded-lg p-3">
            <h4 class="text-xs font-semibold text-text-primary mb-2 pb-1 border-b border-border-strong">
                <i class="fa-solid fa-user mr-1"></i> Pemberi Tugas
            </h4>
            <p class="text-sm text-text-primary font-medium">{{ $spk->pemberi_tugas_nama }}</p>
            <p class="text-xs text-text-secondary whitespace-pre-wrap">{{ $spk->pemberi_tugas_alamat }}</p>
        </div>
        <div class="border border-border-strong rounded-lg p-3">
            <h4 class="text-xs font-semibold text-text-primary mb-2 pb-1 border-b border-border-strong">
                <i class="fa-solid fa-pen mr-1"></i> Yang Bertanda Tangan
            </h4>
            <p class="text-sm text-text-primary font-medium">{{ $spk->signer_nama }}</p>
            <p class="text-xs text-text-secondary">{{ $spk->signer_jabatan }}</p>
        </div>
    </div>

    {{-- Daftar Item Pekerjaan --}}
    <div class="mb-4">
        <p class="text-xs font-semibold text-text-label mb-2">Item Pekerjaan</p>
        <div class="overflow-x-auto border border-border-strong rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-text-label">No</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-text-label">Kode</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-text-label">Keterangan</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-text-label">Volume</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-text-label">Satuan</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-text-label">Harga</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-text-label">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($spk->items as $item)
                        @foreach (data_get($item, 'details', []) as $detail)
                            <tr class="hover:bg-surface-secondary">
                                <td class="px-3 py-2 text-center text-text-primary">{{ data_get($item, 'no', $loop->parent->iteration) }}</td>
                                <td class="px-3 py-2 text-center text-text-primary">{{ data_get($item, 'kode') ?? '-' }}</td>
                                <td class="px-3 py-2 text-text-primary">{{ $detail['keterangan'] }}</td>
                                <td class="px-3 py-2 text-center text-text-primary">{{ $detail['volume'] }}</td>
                                <td class="px-3 py-2 text-center text-text-primary">{{ $detail['satuan'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-right text-text-primary">{{ number_format($detail['harga'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right font-medium text-text-primary">{{ number_format($detail['jumlah'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-text-secondary">Tidak ada item pekerjaan</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-surface-secondary">
                    <tr>
                        <td colspan="6" class="px-3 py-2 text-right font-bold text-text-primary">Total</td>
                        <td class="px-3 py-2 text-right font-bold text-primary">Rp {{ number_format($spk->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Info Sistem --}}
    <div class="pt-3 border-t border-border-strong flex gap-4 text-xs text-text-secondary">
        <span><i class="fa-solid fa-clock mr-1"></i> Dibuat: {{ $spk->created_at->format('d/m/Y H:i') }}</span>
        <span><i class="fa-solid fa-edit mr-1"></i> Diperbarui: {{ $spk->updated_at->format('d/m/Y H:i') }}</span>
    </div>

</x-modal>
