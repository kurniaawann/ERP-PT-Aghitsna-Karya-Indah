{{-- Table Data Semen (read-only rekap) --}}
<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">

                {{-- Header Tabel --}}
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="p-2 text-left">No</th>
                        <th class="p-2 text-left">DO</th>
                        <th class="p-2 text-left">Tanggal</th>
                        <th class="p-2 text-left">Nama Proyek</th>
                        <th class="p-2 text-center">Jumlah (zak)</th>
                        <th class="p-2 text-right">Harga</th>
                        <th class="p-2 text-right">Total</th>
                        <th class="p-2 text-left">Tanggal Lunas</th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody>
                    @forelse($cements as $cement)
                        <tr class="border-t hover:bg-surface-secondary">
                            <td class="p-2 font-medium text-primary">{{ $cement->no }}</td>
                            <td class="p-2">{{ $cement->do_no ?: '-' }}</td>
                            <td class="p-2">{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>
                            <td class="p-2">{{ $cement->nama_proyek }}</td>
                            <td class="p-2 text-center">{{ number_format($cement->jumlah, 0, ',', '.') }}</td>
                            <td class="p-2 text-right">{{ 'Rp ' . number_format($cement->harga, 0, ',', '.') }}</td>
                            <td class="p-2 text-right font-medium">
                                {{ 'Rp ' . number_format($cement->total, 0, ',', '.') }}
                            </td>
                            <td class="p-2">{{ $cement->tanggal_lunas?->format('d M Y') ?: '-' }}</td>
                        </tr>
                    @empty
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
