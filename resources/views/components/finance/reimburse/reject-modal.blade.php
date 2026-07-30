{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL REJECT REIMBURSE
     Konfirmasi penolakan reimbursement (Super Admin only).
     Menampilkan jumlah dan total amount dari reimburse yang dipilih.
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-modal id="rejectModal" title="Tolak Reimbursement" action="{{ route('reimburse.reject') }}" method="POST"
    buttonText="Tolak">

    {{-- ─── Info Konfirmasi ─────────────────────────────────────────────────── --}}
    <div class="mb-4">
        <div class="flex items-center gap-3 p-4 bg-error-light border border-error rounded-lg">
            <div class="flex-shrink-0">
                <svg class="w-10 h-10 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-text-heading">Konfirmasi Penolakan</h3>
                <p class="text-sm text-text-label mt-1">
                    Apakah Anda yakin ingin menolak <span id="reject-count-text"
                        class="font-semibold text-error">0</span> reimburse yang dipilih?
                </p>
            </div>
        </div>
    </div>

    {{-- ─── Total Amount ───────────────────────────────────────────────────── --}}
    <div class="mb-3 p-3 bg-surface-secondary border border-border-strong rounded-lg">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-text-primary">Total Amount:</span>
            <span id="reject-total-modal" class="text-lg font-bold text-error">Rp 0</span>
        </div>
    </div>

    {{-- ─── Catatan Penting ────────────────────────────────────────────────── --}}
    <div class="mb-3">
        <p class="text-xs text-text-secondary">
            <i class="fa-solid fa-info-circle"></i>
            Reimburse yang ditolak akan berubah status menjadi "Ditolak" dan tidak dapat diajukan kembali.
        </p>
    </div>

    {{-- ─── Container Hidden Inputs (diisi oleh JavaScript) ────────────────── --}}
    <div id="reject-hidden-inputs"></div>
</x-modal>
