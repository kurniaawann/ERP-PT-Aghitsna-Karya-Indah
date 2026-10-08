{{-- =====================================================================
     Pilihan Rekap Proyek (opsional) pada form Invoice Proyek (tambah/edit).
     Dipakai role admin & superadmin.

     Saat rekap dipilih, panel ringkasan menampilkan:
     - Nilai Proyek   : Total RAB rekap.
     - Sudah Ditagih  : total invoice lain yang sudah ditautkan ke rekap.
     - Invoice Ini    : nilai tagihan invoice ini (live dari item & diskon).
     - Sisa Tagihan   : Nilai Proyek - Sudah Ditagih - Invoice Ini.
     Nilai tagihan = total item setelah diskon, SEBELUM PPN (sebanding
     dengan Total RAB). Field lain tetap bebas diisi; field kosong (nama
     proyek / lokasi / penerima) di-prefill dari rekap tanpa dikunci.
     Logika JS: resources/js/pages/finance/project-invoices/index.js
     (initRecapPickers / refreshRecapSummary).

     Props:
     - projectRecaps : Collection opsi dari ProyekInvoiceService::getProjectRecapOptions()
     - invoice       : InvoiceProyek yang diedit (null untuk modal tambah)
     ===================================================================== --}}
@props([
    'projectRecaps' => collect(),
    'invoice' => null,
])

@php
    $selectedRecapId = $invoice?->project_recap_id;
    // Nilai tagihan invoice ini yang tersimpan: dikurangkan dari "Sudah
    // Ditagih" rekap asal agar tidak terhitung dua kali saat diedit.
    $currentBilled = $invoice && $invoice->project_recap_id ? $invoice->getBilledAmount() : 0;
@endphp

<div class="mb-3 p-3 border border-border-strong rounded-lg bg-surface-secondary" data-recap-picker
    data-current-recap="{{ $invoice?->project_recap_id }}" data-current-billed="{{ $currentBilled }}">
    <label class="block text-text-primary font-semibold mb-1">
        <i class="fa-solid fa-diagram-project text-primary"></i> Rekap Proyek (Opsional)
    </label>

    @if ($projectRecaps->isNotEmpty())
        <select name="project_recap_id" class="recap-select w-full border border-border-strong rounded-lg p-2 bg-surface-base text-text-input">
            <option value="">-- Tidak ditautkan ke Rekap Proyek --</option>
            @foreach ($projectRecaps as $recap)
                <option value="{{ $recap['id'] }}" data-total="{{ $recap['total'] }}"
                    data-billed="{{ $recap['billed'] }}" data-project-name="{{ $recap['project_name'] }}"
                    data-location="{{ $recap['location'] }}" data-recipient="{{ $recap['recipient'] }}"
                    @selected((string) $selectedRecapId === (string) $recap['id'])>
                    {{ $recap['id'] }} — {{ $recap['project_name'] }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-text-secondary mt-1">
            Tautkan invoice ke rekap untuk memantau sisa tagihan proyek. Field lain tetap bisa diisi/diubah bebas.
        </p>
    @else
        <input type="hidden" name="project_recap_id" value="">
        <p class="text-sm text-text-secondary">Belum ada data Rekap Proyek. Invoice tetap bisa disimpan tanpa tautan rekap.</p>
    @endif

    <div class="recap-summary hidden mt-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <div class="rounded-lg border border-border bg-surface-base p-2">
                <p class="text-xs text-text-secondary">Nilai Proyek</p>
                <p class="recap-summary-total text-sm font-bold text-text-primary">Rp 0</p>
            </div>
            <div class="rounded-lg border border-border bg-surface-base p-2">
                <p class="text-xs text-text-secondary">Sudah Ditagih</p>
                <p class="recap-summary-billed text-sm font-bold text-info">Rp 0</p>
            </div>
            <div class="rounded-lg border border-border bg-surface-base p-2">
                <p class="text-xs text-text-secondary">Invoice Ini</p>
                <p class="recap-summary-current text-sm font-bold text-primary">Rp 0</p>
            </div>
            <div class="rounded-lg border border-border bg-surface-base p-2">
                <p class="text-xs text-text-secondary">Sisa Tagihan</p>
                <p class="recap-summary-remaining text-sm font-bold text-success">Rp 0</p>
            </div>
        </div>
        <p class="recap-summary-over hidden mt-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded text-xs">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span class="recap-summary-over-text">Total tagihan melebihi nilai proyek.</span>
        </p>
        <p class="text-xs text-text-secondary mt-1">
            Nilai tagihan dihitung dari total item setelah diskon (sebelum PPN).
        </p>
    </div>
</div>
