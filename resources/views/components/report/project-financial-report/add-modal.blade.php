{{-- Modal Tambah Transaksi Laporan Keuangan Proyek (global, dari halaman daftar) --}}
@php
    $categoriesJson = $categories->map(fn ($cat) => [
        'id' => $cat->id,
        'name' => $cat->name,
        'type' => $cat->type,
    ])->values()->toJson();
@endphp

<x-modal id="addModal" title="Tambah Transaksi" action="{{ route('project-financial-report.store') }}"
    method="POST" buttonText="Simpan" enctype="multipart/form-data" size="4xl">

    {{-- Rekap Proyek --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Rekap Proyek <span class="text-error">*</span></label>
        <select name="project_recap_id" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Rekap Proyek tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Rekap Proyek --</option>
            @foreach ($rekapOptions as $rekap)
                <option value="{{ $rekap->id }}">
                    {{ $rekap->id }} — {{ $rekap->project_name }}{{ $rekap->location ? ' (' . $rekap->location . ')' : '' }}
                </option>
            @endforeach
        </select>
        @if ($rekapOptions->isEmpty())
            <p class="text-xs text-error mt-1">
                Belum ada Rekap Proyek. Buat rekap terlebih dahulu melalui menu
                <a href="{{ route('recap-proyek.index') }}" target="_blank" class="underline">Rekap Proyek</a>.
            </p>
        @endif
    </div>

    <hr class="my-4">

    {{-- Detail Transaksi (Struktur Dinamis) --}}
    <div class="mb-3">
        <h6 class="text-text-primary font-semibold mb-3">Detail Transaksi (Pilih Kategori)</h6>
        <div class="text-xs text-text-secondary mb-4 p-2 bg-surface-secondary rounded">
            <p class="mb-1"><strong>Struktur:</strong> Pilih Kategori lalu isi Keterangan & Jumlah. Boleh menambah
                lebih dari satu transaksi.</p>
            <p>Kategori <span class="text-success font-semibold">Pemasukan</span> otomatis menjadi
                <strong>Jumlah Pemasukan</strong>, kategori <span class="text-error font-semibold">Pengeluaran</span>
                otomatis menjadi <strong>Jumlah Pengeluaran</strong>.</p>
        </div>
    </div>

    {{-- Container Transaksi --}}
    <div id="transactionsContainer" class="space-y-4 mb-3"
        data-categories="{{ $categoriesJson }}"></div>

    <button type="button" onclick="addTransactionBlock('transactionsContainer')" class="btn btn-outline-primary w-full">
        <i class="fa-solid fa-plus"></i> Tambah Transaksi
    </button>

    <hr class="my-4">

    {{-- Total Keseluruhan --}}
    <div class="flex justify-end mb-3">
        <div class="bg-success-light border-2 border-success rounded p-4 w-full">
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-success border-b border-success pb-2">
                    <span><strong>Total Pemasukan:</strong></span>
                    <span id="transactionsContainer-totalIncome" class="font-semibold">Rp 0</span>
                </div>
                <div class="flex justify-between text-sm text-success border-b border-success pb-2">
                    <span><strong>Total Pengeluaran:</strong></span>
                    <span id="transactionsContainer-totalExpense" class="font-semibold">Rp 0</span>
                </div>
                <div class="flex justify-between text-lg text-success">
                    <span><strong>Saldo:</strong></span>
                    <p class="font-bold text-2xl text-success"><span id="transactionsContainer-balance">Rp 0</span></p>
                </div>
            </div>
        </div>
    </div>
</x-modal>
