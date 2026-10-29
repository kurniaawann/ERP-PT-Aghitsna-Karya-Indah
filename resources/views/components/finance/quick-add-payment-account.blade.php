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

     Catatan: modal quick-add TIDAK membuat elemen <form> (x-fi.modal tanpa
     action) untuk mencegah <form> bersarang di dalam form induk.

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
    <x-fi.button id="{{ $btnId }}" color="gray" size="sm" icon="plus"
        onclick="openModal('{{ $modalId }}')" title="Tambah rekening pembayaran baru">
        Rekening Baru
    </x-fi.button>
</div>

<x-fi.modal id="{{ $modalId }}" heading="Tambah Rekening Pembayaran" icon="building-library" width="md">
    <input type="hidden" name="quick_add_url" value="{{ $createUrl }}">
    <input type="hidden" name="quick_add_target" value="{{ $targetSelector }}">
    <input type="hidden" name="quick_add_mode" value="{{ $mode }}">
    <input type="hidden" name="quick_add_container" value="{{ $checkboxContainer }}">

    <div class="grid gap-y-5">
        <x-fi.field label="Nama Bank" for="quick-add-bank-{{ $uid }}" required>
            <x-fi.input id="quick-add-bank-{{ $uid }}" name="bank_name" class="quick-add-acc-field"
                placeholder="Contoh: Bank BCA" maxlength="255" />
        </x-fi.field>

        <x-fi.field label="Nomor Rekening" for="quick-add-number-{{ $uid }}" required>
            <x-fi.input id="quick-add-number-{{ $uid }}" name="account_number" class="quick-add-acc-field"
                placeholder="Contoh: 1234567890" maxlength="255" inputmode="numeric" />
        </x-fi.field>

        <x-fi.field label="Nama Pemilik Rekening" for="quick-add-holder-{{ $uid }}" required>
            <x-fi.input id="quick-add-holder-{{ $uid }}" name="account_holder" class="quick-add-acc-field"
                placeholder="Contoh: PT Aghitsna Karya Indah" maxlength="255" />
        </x-fi.field>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4">
        <x-fi.button data-quick-add-submit>Simpan</x-fi.button>
        <x-fi.button color="gray" onclick="closeModal('{{ $modalId }}')">Batal</x-fi.button>
    </div>
</x-fi.modal>
