{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL APPROVE REIMBURSE (gaya Filament)
     Konfirmasi persetujuan reimbursement (Admin only).
     Menampilkan jumlah dan total amount dari reimburse draft yang dipilih.
     Setelah disetujui, pratinjau PDF reimburse tersebut langsung dibuka
     di dalam halaman.
     JS: #approve-count-text, #approve-total-modal, #approve-hidden-inputs,
     #submit-btn-approveModal (resources/js/pages/finance/reimburse/index.js).
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-fi.modal id="approveModal" heading="Setujui Reimbursement" icon="check-circle" color="success" width="md"
    :action="route('reimburse.approve')" submitLabel="Setujui">

    {{-- ─── Info Konfirmasi ─────────────────────────────────────────────────── --}}
    <div class="rounded-lg bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-600/10">
        <h3 class="text-sm font-semibold text-gray-950">Konfirmasi Persetujuan</h3>
        <p class="mt-1 text-sm text-gray-600">
            Apakah Anda yakin ingin menyetujui <span id="approve-count-text"
                class="font-semibold text-emerald-700">0</span> reimburse yang dipilih?
        </p>
    </div>

    {{-- ─── Total Amount ───────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-950/5">
        <span class="text-sm font-medium text-gray-700">Total Amount</span>
        <span id="approve-total-modal" class="text-lg font-semibold text-emerald-700">Rp 0</span>
    </div>

    {{-- ─── Catatan Penting ────────────────────────────────────────────────── --}}
    <p class="flex gap-1.5 text-sm text-gray-500">
        <x-fi.icon name="information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
        <span>Reimburse yang disetujui akan langsung berubah status menjadi "Disetujui" dan tidak dapat diubah kembali.
            Hanya reimburse berstatus Draft yang diproses. Setelah disetujui, dokumen PDF-nya langsung ditampilkan.</span>
    </p>

    {{-- ─── Container Hidden Inputs (diisi oleh JavaScript) ────────────────── --}}
    <div id="approve-hidden-inputs" class="hidden"></div>
</x-fi.modal>
