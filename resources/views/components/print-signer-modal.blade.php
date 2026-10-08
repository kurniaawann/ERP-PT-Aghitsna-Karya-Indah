{{--
    Modal Penandatangan Laporan (wajib sebelum cetak PDF/Excel)

    Muncul saat link export ber-atribut data-require-signer diklik (Laporan
    Pengeluaran / Kas Kantor). Pilihan dikirim sebagai query signer_id lalu
    pratinjau dokumen dibuka (resources/js/shared/print-signer.js). Di PDF &
    Excel dicetak satu blok tanda tangan di kanan: jabatan, gambar tanda
    tangan (bila ada), ( Nama ).

    Data: $executives (App\View\Components\PrintSignerModal) — Data
    Penandatangan milik user login, seperti pilihan penandatangan di invoice.
--}}
<x-modal id="printSignerModal" title="Penandatangan Laporan" onConfirm="confirmPrintSigner()" buttonText="Lanjut Print">
    <p class="text-sm text-text-secondary">
        Pilih penandatangan yang dicetak di kanan bawah laporan
        <strong class="print-signer-format text-text-primary"></strong>.
    </p>

    @if ($executives->isEmpty())
        <div class="p-3 bg-warning-light border border-warning rounded-lg text-sm text-warning">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            Belum ada Data Penandatangan. Tambahkan dulu di menu
            <a href="{{ route('executive.index') }}" class="font-semibold underline">Data Penandatangan</a>.
        </div>
    @endif

    <div>
        <label for="print-signer-select" class="block text-text-primary mb-1">
            Nama Penandatangan <span class="text-error">*</span>
        </label>
        <select id="print-signer-select" class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            oninput="this.setCustomValidity('')" @disabled($executives->isEmpty())>
            <option value="">-- Pilih Nama Penandatangan --</option>
            @foreach ($executives as $executive)
                <option value="{{ $executive->id }}">{{ $executive->name }}{{ $executive->position ? ' (' . $executive->position . ')' : '' }}</option>
            @endforeach
        </select>
    </div>
</x-modal>
