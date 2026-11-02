{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL REJECT REIMBURSE (gaya Filament)
     Konfirmasi penolakan reimbursement (Admin only).
     Menampilkan jumlah dan total amount dari reimburse draft yang dipilih.
     JS: #reject-count-text, #reject-total-modal, #reject-hidden-inputs,
     #submit-btn-rejectModal (resources/js/pages/finance/reimburse/index.js).
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-fi.modal id="rejectModal" heading="Tolak Reimbursement" icon="x-mark" color="danger" width="md"
    :action="route('reimburse.reject')" submitLabel="Tolak">

    {{-- ─── Info Konfirmasi ─────────────────────────────────────────────────── --}}
    <div class="rounded-lg bg-red-50 p-4 ring-1 ring-inset ring-red-600/10">
        <h3 class="text-sm font-semibold text-gray-950">Konfirmasi Penolakan</h3>
        <p class="mt-1 text-sm text-gray-600">
            Apakah Anda yakin ingin menolak <span id="reject-count-text"
                class="font-semibold text-red-700">0</span> reimburse yang dipilih?
        </p>
    </div>

    {{-- ─── Total Amount ───────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-950/5">
        <span class="text-sm font-medium text-gray-700">Total Amount</span>
        <span id="reject-total-modal" class="text-lg font-semibold text-red-700">Rp 0</span>
    </div>

    {{-- ─── Catatan Penting ────────────────────────────────────────────────── --}}
    <p class="flex gap-1.5 text-sm text-gray-500">
        <x-fi.icon name="information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
        <span>Reimburse yang ditolak akan berubah status menjadi "Ditolak" dan tidak dapat diajukan kembali.</span>
    </p>

    {{-- ─── Container Hidden Inputs (diisi oleh JavaScript) ────────────────── --}}
    <div id="reject-hidden-inputs" class="hidden"></div>
</x-fi.modal>
