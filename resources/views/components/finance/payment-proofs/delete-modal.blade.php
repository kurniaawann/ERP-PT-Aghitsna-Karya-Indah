<x-modal id="deleteModal-{{ $paymentProof->id }}" title="Hapus Bukti Pembayaran"
    action="{{ route('payment-proofs.destroy', $paymentProof->id) }}" method="DELETE" buttonText="Hapus">
    <p class="text-text-label">Apakah kamu yakin ingin menghapus bukti pembayaran ini?</p>
</x-modal>
