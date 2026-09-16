{{-- =====================================================================
     Quick Add Rekening Pembayaran (komponen reusable).
     Menampilkan tombol "+" (Rekening Baru) untuk membuat rekening
     pembayaran baru secara cepat dari dalam form apa pun (modal invoice,
     kwintansi, RAB, dsb) tanpa harus pindah ke modul Rekening Pembayaran.

     Rekening dibuat via AJAX (POST payment-accounts.storeAjax), lalu
     rekening baru disisipkan ke target pada form yang sama dan ditandai
     terpilih:
      - Mode "select"   : opsi baru ditambahkan ke
                          <select name="payment_account_id"> dan dipilih.
      - Mode "checkbox" : checkbox baru disisipkan ke grup rekening dan
                          langsung dicentang (struktur di-clone dari item
                          pertama yang ada).

     Catatan: modal quick-add TIDAK membuat elemen <form> (pakai hideFooter)
     untuk mencegah <form> bersarang di dalam form induk.

     Props:
     - targetSelector: CSS selector select tujuan (mode "select").
     - checkboxContainer: CSS selector container checkbox tujuan (mode "checkbox").
     - mode: "select" | "checkbox" (default "checkbox").
     - idSuffix: string unik agar id modal tidak bentrok antar instance.
     ===================================================================== --}}
@props([
    'targetSelector' => null,
    'checkboxContainer' => null,
    'mode' => 'checkbox',
    'idSuffix' => null,
])

@php
    $uid = $idSuffix ?: md5($targetSelector ?: uniqid(mt_rand(), true));
    $modalId = "quickAddPaymentAccountModal-{$uid}";
    $btnId = "quickAddPaymentAccountBtn-{$uid}";
    $createUrl = route('payment-accounts.storeAjax');
@endphp

<div class="inline-block">
    <button type="button" id="{{ $btnId }}"
        class="flex items-center gap-1 text-xs font-semibold text-primary hover:text-primary-hover border border-primary/30 hover:border-primary rounded px-2 py-1 whitespace-nowrap transition-colors"
        onclick="openModal('{{ $modalId }}')"
        title="Tambah rekening pembayaran baru">
        <i class="fa-solid fa-plus"></i> Rekening Baru
    </button>
</div>

<x-modal id="{{ $modalId }}" title="Tambah Rekening Pembayaran" size="md" :hideFooter="true">
    <input type="hidden" name="quick_add_url" value="{{ $createUrl }}">
    <input type="hidden" name="quick_add_target" value="{{ $targetSelector }}">
    <input type="hidden" name="quick_add_mode" value="{{ $mode }}">
    <input type="hidden" name="quick_add_container" value="{{ $checkboxContainer }}">

    <div class="space-y-4">
        <div>
            <label class="block text-text-primary mb-1">Nama Bank <span class="text-error">*</span></label>
            <input type="text" name="bank_name" class="quick-add-acc-field w-full border rounded p-2"
                placeholder="Contoh: Bank BCA" maxlength="255"
                oninvalid="this.setCustomValidity('Nama bank wajib diisi')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Nomor Rekening <span class="text-error">*</span></label>
            <input type="text" name="account_number" class="quick-add-acc-field w-full border rounded p-2"
                placeholder="Contoh: 1234567890" maxlength="255"
                oninvalid="this.setCustomValidity('Nomor rekening wajib diisi')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Nama Pemilik Rekening <span class="text-error">*</span></label>
            <input type="text" name="account_holder" class="quick-add-acc-field w-full border rounded p-2"
                placeholder="Contoh: PT Aghitsna Karya Indah" maxlength="255"
                oninvalid="this.setCustomValidity('Nama pemilik rekening wajib diisi')"
                oninput="this.setCustomValidity('')">
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" class="bg-button-cancel px-4 py-2 rounded hover:bg-button-cancel-hover"
            onclick="closeModal('{{ $modalId }}')">
            Batal
        </button>
        <button type="button" data-quick-add-submit
            class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded">
            Simpan
        </button>
    </div>
</x-modal>