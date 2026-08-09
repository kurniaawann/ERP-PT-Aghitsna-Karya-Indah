{{-- =====================================================================
     Halaman: Bukti Pembayaran (Payment Proofs)
     Tujuan: Mengelola bukti pembayaran untuk invoice proyek, invoice
             alumunium, dan invoice barang, dengan hero
             header, summary cards, filter modul/jenis invoice & pencarian,
             CRUD bukti (upload gambar), dan hapus massal.
     Data dari PaymentProofController@index:
     - $paymentProofs    : Paginator PaymentProof (10/halaman) hasil filter
                           module_type, invoice_type, search; untuk admin
                           hanya menampilkan bukti miliknya (created_by).
     - $totalProofs      : Total jumlah bukti hasil filter.
     - $projectProofs    : Jumlah bukti bertipe invoice 'proyek'.
     - $alumuniumProofs  : Jumlah bukti bertipe 'alumunium' (non-admin).
     - $barangProofs     : Jumlah bukti bertipe 'barang' (non-admin).
     - $moduleOptions    : Opsi filter modul ('finance' = Keuangan).
     - $invoiceTypeOptions: Opsi filter jenis invoice; berbeda antara
                            admin (Invoice) dan non-admin
                            (Invoice Proyek, Alumunium, Barang).
     - $availableInvoices: Data invoice yang tersedia untuk dipilih pada
                           form tambah/edit; di-expose ke
                           window.paymentProofInvoiceData via @json.
     - $invoiceLookup     : Struktur lookup invoice per modul (dipakai JS).
     Komponen yang di-include:
     - components.finance.payment-proofs.table     : tabel bukti pembayaran
     - components.finance.payment-proofs.add-modal / edit-modal : modal CRUD
     - x-buttons.delete-button / add-button        : tombol aksi header
     - x-pagination                                : navigasi halaman
     - x-modal (deleteModal)                       : konfirmasi hapus
     JS: @vite('resources/js/pages/finance/payment-proofs/index.js')
         + inline script @push('scripts') untuk bind submit form edit.
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Bukti Pembayaran')

@section('content')
    <div class="space-y-4">
        {{-- Section: Header --}}
        {{-- Hero header: teks dan penjelasan disesuaikan per role.
             Alur auth()->user()->isAdmin():
             - Admin  : kelola bukti 'invoice'.
             - Non-admin: kelola bukti 'invoice proyek, alumunium, dan barang'. --}}
        <div
            class="rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 p-5 sm:p-6 text-white shadow-xl overflow-hidden relative">
            <div class="absolute inset-0 opacity-20"
                style="background-image: radial-gradient(circle at top right, rgba(255,255,255,0.55), transparent 30%), radial-gradient(circle at bottom left, rgba(59,130,246,0.6), transparent 28%);">
            </div>
            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl space-y-2">
                    <span
                        class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-white/90">Finance
                        Module</span>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Bukti Pembayaran</h1>
                        <p class="mt-2 text-sm sm:text-base text-white/80">Kelola bukti pembayaran {{ auth()->user()->isAdmin() ? 'invoice' : 'invoice proyek, alumunium, dan barang' }}. Tahap pembayaran hanya berlaku untuk {{ auth()->user()->isAdmin() ? 'invoice' : 'invoice proyek' }}.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Bukti" />
                </div>
            </div>
        </div>

        {{-- Section: Summary Cards --}}
        {{-- Empat kartu ringkasan (Total Bukti, Invoice/Proyek, Alumunium,
             Invoice Barang). Kartu kedua memakai nama berbeda sesuai role:
             admin = 'Invoice', non-admin = 'Invoice Proyek'. --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div
                class="group rounded-2xl border border-border-strong bg-surface-base p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-text-label">Total Bukti</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-text-primary">
                            {{ number_format($totalProofs) }}</p>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-surface-secondary text-text-label ring-1 ring-border-light transition-colors group-hover:bg-primary-light group-hover:text-primary">
                        <i class="fa-solid fa-file-signature text-base"></i>
                    </div>
                </div>
            </div>

            <div
                class="group rounded-2xl border border-primary/20 bg-gradient-to-br from-primary-light to-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">{{ auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek' }}</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-primary">{{ number_format($projectProofs) }}
                        </p>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-primary ring-1 ring-primary/10 transition-colors group-hover:bg-primary group-hover:text-white">
                        <i class="fa-solid fa-diagram-project text-base"></i>
                    </div>
                </div>
            </div>

            <div
                class="group rounded-2xl border border-warning/20 bg-gradient-to-br from-warning-light to-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-warning">Invoice Alumunium</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-warning">{{ number_format($alumuniumProofs) }}
                        </p>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-warning ring-1 ring-warning/10 transition-colors group-hover:bg-warning group-hover:text-white">
                        <i class="fa-solid fa-boxes-stacked text-base"></i>
                    </div>
                </div>
            </div>

            <div
                class="group rounded-2xl border border-secondary/20 bg-gradient-to-br from-secondary-light to-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-secondary">Invoice Barang</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-secondary">
                            {{ number_format($barangProofs) }}</p>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-secondary ring-1 ring-secondary/10 transition-colors group-hover:bg-secondary group-hover:text-white">
                        <i class="fa-solid fa-box-open text-base"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Filter --}}
        {{-- Form GET yang auto-submit (requestSubmit) saat input search
             berubah atau select modul/jenis invoice dipilih.
             Select dipakai @selected untuk mempertahankan nilai filter
             yang sedang aktif saat halaman direload. --}}
        <div class="rounded-xl border border-border-strong bg-surface-base p-4 shadow-sm">
            <form method="GET" action="{{ route('payment-proofs.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full rounded-lg border border-border-strong px-3 py-2 text-sm"
                    placeholder="Cari invoice atau file..." oninput="this.form.requestSubmit()">

                <select name="module_type" class="w-full rounded-lg border border-border-strong px-3 py-2 text-sm"
                    onchange="this.form.requestSubmit()">
                    <option value="">Semua Modul</option>
                    @foreach ($moduleOptions as $module)
                        <option value="{{ $module['value'] }}" @selected(request('module_type') === $module['value'])>{{ $module['label'] }}</option>
                    @endforeach
                </select>

                <select name="invoice_type" class="w-full rounded-lg border border-border-strong px-3 py-2 text-sm"
                    onchange="this.form.requestSubmit()">
                    <option value="">Semua Jenis Invoice</option>
                    @foreach ($invoiceTypeOptions as $invoiceType)
                        <option value="{{ $invoiceType['value'] }}" @selected(request('invoice_type') === $invoiceType['value'])>{{ $invoiceType['label'] }}
                        </option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <a href="{{ route('payment-proofs.index') }}"
                        class="rounded-lg bg-btn-delete px-4 py-2 text-sm font-medium text-white transition hover:bg-btn-delete-hover">Reset</a>
                </div>
            </form>
        </div>

        {{-- Section: Table --}}
        {{-- Tabel daftar bukti pembayaran hasil filter (lihat komponen
             components.finance.payment-proofs.table). --}}
        @include('components.finance.payment-proofs.table')
    </div>

    {{-- Section: Pagination --}}
    <div class="mt-4">
        <x-pagination :paginator="$paymentProofs" />
    </div>

    {{-- Section: Modals --}}
    {{-- Modal tambah bukti pembayaran (pilih invoice + upload gambar). --}}
    @include('components.finance.payment-proofs.add-modal')

    {{-- Satu modal edit dirender untuk setiap bukti di halaman ini agar
         data form (termasuk invoice terpilih) terpisah antar baris. --}}
    @foreach ($paymentProofs as $paymentProof)
        @include('components.finance.payment-proofs.edit-modal', ['paymentProof' => $paymentProof])
    @endforeach

    {{-- Section: Modal Konfirmasi Hapus --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>
@endsection

@push('scripts')
    {{-- ==================== Section: Scripts ==================== --}}
    {{-- Data invoice yang tersedia untuk dipilih di form tambah/edit
         di-expose ke window.paymentProofInvoiceData.
         Alur @json($availableInvoices): array berstruktur
         ['finance' => ['proyek' => [...], 'alumunium' => [...],
         'barang' => [...]]], berisi daftar invoice beserta
         sisa tagihan/payment stage yang sudah dihitung service. --}}
    <script>
        /* global handleFormSubmit, parseCurrencyInput, formatRupiah, bindPaymentProofForm, validatePaymentProofAmount */
        window.paymentProofInvoiceData = @json($availableInvoices);
    </script>
    @vite('resources/js/pages/finance/payment-proofs/index.js')

    {{-- Bind event submit untuk setiap form edit modal (satu per bukti).
         Alur: setelah DOM ready, tiap #editModal-{id} form diberi listener
         yang mencegah submit ganda via handleFormSubmit() saat menyimpan. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @foreach ($paymentProofs as $paymentProof)
                (function () {
                    var editForm = document.querySelector('#editModal-{{ $paymentProof->id }} form');
                    if (editForm) {
                        editForm.addEventListener('submit', function (e) {
                            var submitBtn = this.querySelector('button[type="submit"]');
                            var originalText = submitBtn.innerHTML;
                            if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
                                e.preventDefault();
                                return false;
                            }
                        });
                    }
                })();
            @endforeach
        });
    </script>
@endpush
