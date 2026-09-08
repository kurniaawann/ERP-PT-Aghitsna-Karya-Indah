{{-- =====================================================================
     Komponen: Delete Bukti Pembayaran (shared modal)
     Dipakai: dirender SEKALI di halaman modul (di luar form modal Edit),
     dipicu dari tombol "Hapus" di components.finance.payment-proofs.manager.
     Action form di-set via JS (openPaymentProofDelete / __ID__ placeholder)
     sebelum modal dibuka, menuju route('payment-proofs.destroy').
     Form hapus dirender sebagai sibling modal (bukan nested di dalam form).
     ===================================================================== --}}
<form id="payment-proof-delete-form" method="POST" action="{{ route('payment-proofs.destroy', '__ID__') }}"
    data-base-url="{{ route('payment-proofs.destroy', '__ID__') }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

<x-modal id="paymentProofDeleteModal" title="Konfirmasi Hapus" :confirmDelete="true"
    onConfirm="document.getElementById('payment-proof-delete-form').submit()"
    buttonText="Ya, Hapus">
    Apakah kamu yakin ingin menghapus bukti pembayaran ini?
</x-modal>
