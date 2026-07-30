@props(['totals'])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
    <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
        <p class="text-xs uppercase tracking-wide text-text-secondary">Total Invoice</p>
        <p class="text-xl font-bold text-text-primary mt-1">Rp
            {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}</p>
    </div>
    <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
        <p class="text-xs uppercase tracking-wide text-text-secondary">Jumlah Invoice</p>
        <p class="text-xl font-bold text-success mt-1">{{ $totals->invoice_count ?? 0 }} invoice</p>
    </div>
    <div class="rounded-xl border border-border-strong p-4 bg-surface-secondary">
        <p class="text-xs uppercase tracking-wide text-text-secondary">Lunas</p>
        <p class="text-xl font-bold text-success mt-1">{{ $totals->paid_count ?? 0 }} invoice</p>
    </div>
</div>
