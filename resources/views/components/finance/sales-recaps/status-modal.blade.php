{{-- Modal Status Pembayaran Rekap Penjualan (gaya Filament, hanya baca).
     Dibuka dari menu Aksi "Status Pembayaran": openModal('statusModal-{id}').
     Status berubah otomatis dari bukti pembayaran, jadi tanpa tombol simpan. --}}
<x-fi.modal id="statusModal-{{ $sale->id_sales_recap }}" heading="Status Pembayaran" icon="check-circle"
    description="Status akan berubah otomatis saat bukti pembayaran sudah diupload.">
    <div class="grid gap-y-5">

        {{-- Info Rekap --}}
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl p-4 text-sm ring-1 ring-gray-950/10 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-gray-500">Proyek</dt>
                <dd class="mt-1 font-semibold text-gray-950">{{ $sale->name_proyek ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Tanggal</dt>
                <dd class="mt-1 text-gray-950">{{ $sale->date->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Total Profit</dt>
                <dd class="mt-1 font-medium tabular-nums text-emerald-600">Rp {{ number_format($sale->total_profit, 0, ',', '.') }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-gray-500">Status Saat Ini</dt>
                <dd class="mt-1">
                    <x-fi.badge :color="$sale->status === 'Lunas' ? 'success' : 'warning'">{{ $sale->status }}</x-fi.badge>
                </dd>
            </div>
        </dl>

        {{-- Informasi --}}
        <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-600/20">
            <x-fi.icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div>
                <p class="font-semibold">Informasi</p>
                <p class="mt-0.5">
                    Setelah bukti pembayaran invoice proyek diupload dan dikaitkan ke rekap penjualan ini,
                    status akan berubah otomatis menjadi <strong>"Lunas"</strong> jika total pembayaran sudah
                    terpenuhi.
                </p>
            </div>
        </div>
    </div>
</x-fi.modal>
