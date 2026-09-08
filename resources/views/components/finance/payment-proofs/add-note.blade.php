{{-- =====================================================================
     Komponen: Payment Proof Add Note (dipakai di dalam modal Add tiap modul)
     Tujuan: Menampilkan informasi bahwa gambar bukti pembayaran diupload
             setelah data tersimpan (melalui tombol Edit), karena nomor
             invoice/rekap baru dibuat setelah data disimpan.
     Alasan desain: Saat Add, data (nomor invoice) belum ada sehingga foto
     bukti bayar belum bisa disimpan. Bagian ini memberi tahu user.
     ===================================================================== --}}
<div class="mb-3 p-3 border border-dashed border-border-strong rounded-lg bg-green-50">
    <label class="flex items-center gap-2 text-sm font-semibold text-text-primary">
        <i class="fa-solid fa-image text-green-600"></i>
        Bukti Pembayaran
    </label>
    <p class="mt-1 text-xs text-text-label leading-relaxed">
        Foto bukti pembayaran di-upload setelah data tersimpan.
        Klik tombol <span class="font-medium">Edit</span> pada data yang baru
        dibuat untuk mengunggah bukti pembayaran (nomor invoice/rekap baru
        dibuat setelah data disimpan).
    </p>
</div>
