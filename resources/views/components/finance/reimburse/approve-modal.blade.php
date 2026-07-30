{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN MODAL APPROVE REIMBURSE
     Konfirmasi persetujuan reimbursement (Super Admin only).
     Menampilkan jumlah dan total amount dari reimburse yang dipilih.
     ═══════════════════════════════════════════════════════════════════════════ --}}
<x-modal id="approveModal" title="Setujui Reimbursement" action="{{ route('reimburse.approve') }}" method="POST"
    buttonText="Setujui">

    {{-- ─── Info Konfirmasi ─────────────────────────────────────────────────── --}}
    <div class="mb-4">
        <div class="flex items-center gap-3 p-4 bg-success-light border border-success rounded-lg">
            <div class="flex-shrink-0">
                <svg class="w-10 h-10 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-text-heading">Konfirmasi Persetujuan</h3>
                <p class="text-sm text-text-label mt-1">
                    Apakah Anda yakin ingin menyetujui <span id="approve-count-text"
                        class="font-semibold text-success">0</span> reimburse yang dipilih?
                </p>
            </div>
        </div>
    </div>

    {{-- ─── Total Amount ───────────────────────────────────────────────────── --}}
    <div class="mb-3 p-3 bg-surface-secondary border border-border-strong rounded-lg">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-text-primary">Total Amount:</span>
            <span id="approve-total-modal" class="text-lg font-bold text-success">Rp 0</span>
        </div>
    </div>

    {{-- ─── Catatan Penting ────────────────────────────────────────────────── --}}
    <div class="mb-3">
        <p class="text-xs text-text-secondary">
            <i class="fa-solid fa-info-circle"></i>
            Reimburse yang disetujui akan langsung berubah status menjadi "Disetujui" dan tidak dapat diubah kembali.
        </p>
    </div>

    {{-- ─── Container Hidden Inputs (diisi oleh JavaScript) ────────────────── --}}
    <div id="approve-hidden-inputs"></div>
</x-modal>
