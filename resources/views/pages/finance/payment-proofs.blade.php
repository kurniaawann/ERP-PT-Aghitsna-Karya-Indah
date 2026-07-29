@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Bukti Pembayaran')

@section('content')
    <div class="space-y-4">
        {{-- Section: Header --}}
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
                        <p class="mt-2 text-sm sm:text-base text-white/80">Kelola bukti pembayaran {{ auth()->user()->isAdmin() ? 'invoice dan rekap penjualan' : 'invoice proyek dan alumunium' }}. Tahap pembayaran hanya berlaku untuk {{ auth()->user()->isAdmin() ? 'invoice' : 'invoice proyek' }}.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Bukti" />
                </div>
            </div>
        </div>

        {{-- Section: Summary Cards --}}
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
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-secondary">Rekap Penjualan</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-secondary">
                            {{ number_format($salesRecapProofs) }}</p>
                    </div>
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-secondary ring-1 ring-secondary/10 transition-colors group-hover:bg-secondary group-hover:text-white">
                        <i class="fa-solid fa-chart-line text-base"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Filter --}}
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
        @include('components.finance.payment-proofs.table')
    </div>

    {{-- Section: Pagination --}}
    <div class="mt-4">
        <x-pagination :paginator="$paymentProofs" />
    </div>

    {{-- Section: Modals --}}
    @include('components.finance.payment-proofs.add-modal')

    @foreach ($paymentProofs as $paymentProof)
        @include('components.finance.payment-proofs.edit-modal', ['paymentProof' => $paymentProof])
    @endforeach

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>
@endsection

@push('scripts')
    <script>
        /* global handleFormSubmit, parseCurrencyInput, formatRupiah, bindPaymentProofForm, validatePaymentProofAmount */
        window.paymentProofInvoiceData = @json($availableInvoices);
    </script>
    @vite('resources/js/pages/finance/payment-proofs/index.js')
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
