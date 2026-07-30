@props(['totals'])

{{-- ==================== Ringkasan Rekap Proyek ==================== --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
        <p class="text-xs uppercase tracking-wide text-text-secondary">Total Invoice</p>
        <p class="text-xl font-bold text-text-primary mt-1">Rp
            {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
        <p class="text-xs uppercase tracking-wide text-text-secondary">Sudah Dibayar</p>
        <p class="text-xl font-bold text-success mt-1">Rp {{ number_format($totals->total_paid ?? 0, 0, ',', '.') }}
        </p>
    </div>
    <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
        <p class="text-xs uppercase tracking-wide text-text-secondary">Sisa Tagihan</p>
        <p class="text-xl font-bold text-warning mt-1">Rp
            {{ number_format($totals->total_remaining ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
        <p class="text-xs uppercase tracking-wide text-text-secondary">Lunas / Belum</p>
        <p class="text-xl font-bold text-primary mt-1">{{ $totals->paid_count ?? 0 }} /
            {{ $totals->unpaid_count ?? 0 }}</p>
    </div>
</div>
