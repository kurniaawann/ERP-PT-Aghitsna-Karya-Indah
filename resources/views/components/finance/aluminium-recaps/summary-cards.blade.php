{{-- Kartu ringkasan Rekap Alumunium (gaya Filament: stat card).
     $totals dari RecapAlumuniumService::buildTotals() (seluruh data periode). --}}
@props(['totals'])

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-fi.card class="grid gap-y-2 p-6">
        <div class="flex items-center gap-x-2 text-sm font-medium text-gray-500">
            <x-fi.icon name="banknotes" class="h-5 w-5 text-gray-400" />
            Total Invoice
        </div>
        <p class="text-2xl font-semibold tracking-tight tabular-nums text-gray-950 sm:text-3xl">
            Rp {{ number_format($totals->total_invoice ?? 0, 0, ',', '.') }}
        </p>
    </x-fi.card>

    <x-fi.card class="grid gap-y-2 p-6">
        <div class="flex items-center gap-x-2 text-sm font-medium text-gray-500">
            <x-fi.icon name="document-text" class="h-5 w-5 text-gray-400" />
            Jumlah Invoice
        </div>
        <p class="text-2xl font-semibold tracking-tight tabular-nums text-gray-950 sm:text-3xl">
            {{ $totals->invoice_count ?? 0 }} <span class="text-base font-medium text-gray-500">invoice</span>
        </p>
    </x-fi.card>

    <x-fi.card class="grid gap-y-2 p-6">
        <div class="flex items-center gap-x-2 text-sm font-medium text-gray-500">
            <x-fi.icon name="check-circle" class="h-5 w-5 text-emerald-500" />
            Lunas
        </div>
        <p class="text-2xl font-semibold tracking-tight tabular-nums text-emerald-600 sm:text-3xl">
            {{ $totals->paid_count ?? 0 }} <span class="text-base font-medium text-gray-500">invoice</span>
        </p>
    </x-fi.card>
</div>
