{{-- =====================================================================
     Komponen: Delete Bukti Pembayaran (shared modal, gaya Filament)
     Dipakai: dirender SEKALI di halaman modul (di luar form Edit),
     dipicu dari tombol "Hapus" di components.finance.payment-proofs.manager.
     Action form di-set via JS (openPaymentProofDelete / __ID__ placeholder)
     sebelum modal dibuka, menuju route('payment-proofs.destroy').
     Form hapus dirender sebagai sibling modal (bukan nested di dalam form).
     Konfirmasi → submitPaymentProofDelete() (tombol #confirm-btn-paymentProofDeleteModal).
     ===================================================================== --}}
<form id="payment-proof-delete-form" method="POST" action="{{ route('payment-proofs.destroy', '__ID__') }}"
    data-base-url="{{ route('payment-proofs.destroy', '__ID__') }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

<x-fi.modal id="paymentProofDeleteModal" heading="Hapus Bukti Pembayaran" icon="trash" color="danger" width="md"
    onConfirm="submitPaymentProofDelete()" submitLabel="Ya, Hapus">
    Apakah kamu yakin ingin menghapus bukti pembayaran ini? Data yang dihapus tidak dapat dikembalikan.
</x-fi.modal>
