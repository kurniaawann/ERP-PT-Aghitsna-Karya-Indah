{{-- =====================================================================
     Pilihan Rekap Proyek (opsional) pada form Invoice Proyek (tambah/edit),
     gaya Filament — diletakkan di dalam x-fi.section "Rekap Proyek".
     Dipakai role admin & superadmin.

     Saat rekap dipilih, panel ringkasan menampilkan:
     - Nilai Proyek   : Total RAB rekap.
     - Sudah Ditagih  : total invoice lain yang sudah ditautkan ke rekap.
     - Invoice Ini    : nilai tagihan invoice ini (live dari item & diskon).
     - Sisa Tagihan   : Nilai Proyek - Sudah Ditagih - Invoice Ini.
     Nilai tagihan = total item setelah diskon, SEBELUM PPN (sebanding
     dengan Total RAB). Field lain tetap bebas diisi; field kosong (nama
     proyek / lokasi / penerima) di-prefill dari rekap tanpa dikunci.
     Logika JS: resources/js/pages/finance/project-invoices/form.js
     (initRecapPickers / refreshRecapSummary).

     Props:
     - projectRecaps : Collection opsi dari ProyekInvoiceService::getProjectRecapOptions()
     - invoice       : InvoiceProyek yang diedit (null untuk tambah)
     ===================================================================== --}}
@props([
    'projectRecaps' => collect(),
    'invoice' => null,
])

@php
    $selectedRecapId = old('project_recap_id', $invoice?->project_recap_id);
    // Nilai tagihan invoice ini yang tersimpan: dikurangkan dari "Sudah
    // Ditagih" rekap asal agar tidak terhitung dua kali saat diedit.
    $currentBilled = $invoice && $invoice->project_recap_id ? $invoice->getBilledAmount() : 0;
@endphp

<div class="grid gap-y-4" data-recap-picker
    data-current-recap="{{ $invoice?->project_recap_id }}" data-current-billed="{{ $currentBilled }}">

    @if ($projectRecaps->isNotEmpty())
        <x-fi.field label="Rekap Proyek" for="project-recap-id"
            hint="Tautkan invoice ke rekap untuk memantau sisa tagihan proyek. Field lain tetap bisa diisi/diubah bebas.">
            <x-fi.select id="project-recap-id" name="project_recap_id" class="recap-select" placeholder="Tidak ditautkan ke Rekap Proyek"
                data-fi-select>
                @foreach ($projectRecaps as $recap)
                    <option value="{{ $recap['id'] }}" data-total="{{ $recap['total'] }}"
                        data-billed="{{ $recap['billed'] }}" data-project-name="{{ $recap['project_name'] }}"
                        data-location="{{ $recap['location'] }}" data-recipient="{{ $recap['recipient'] }}"
                        @selected((string) $selectedRecapId === (string) $recap['id'])>
                        {{ $recap['id'] }} — {{ $recap['project_name'] }}
                    </option>
                @endforeach
            </x-fi.select>
        </x-fi.field>
    @else
        <input type="hidden" name="project_recap_id" value="">
        <p class="text-sm text-gray-500">Belum ada data Rekap Proyek. Invoice tetap bisa disimpan tanpa tautan rekap.</p>
    @endif

    <div class="recap-summary hidden">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                <p class="text-sm text-gray-500">Nilai Proyek</p>
                <p class="recap-summary-total mt-1 font-semibold tabular-nums text-gray-950">Rp 0</p>
            </div>
            <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                <p class="text-sm text-gray-500">Sudah Ditagih</p>
                <p class="recap-summary-billed mt-1 font-semibold tabular-nums text-sky-600">Rp 0</p>
            </div>
            <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                <p class="text-sm text-gray-500">Invoice Ini</p>
                <p class="recap-summary-current mt-1 font-semibold tabular-nums text-primary">Rp 0</p>
            </div>
            <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                <p class="text-sm text-gray-500">Sisa Tagihan</p>
                {{-- text-success / text-error di-toggle JS sesuai sisa ≥ 0 / < 0 --}}
                <p class="recap-summary-remaining text-success mt-1 font-semibold tabular-nums">Rp 0</p>
            </div>
        </div>
        <p class="recap-summary-over mt-3 hidden rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-600/20">
            <x-fi.icon name="exclamation-triangle" class="-mt-0.5 me-1 inline h-4 w-4 text-red-600" />
            <span class="recap-summary-over-text">Total tagihan melebihi nilai proyek.</span>
        </p>
        <p class="mt-2 text-sm text-gray-500">
            Nilai tagihan dihitung dari total item setelah diskon (sebelum PPN).
        </p>
    </div>
</div>
