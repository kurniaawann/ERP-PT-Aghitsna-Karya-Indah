{{-- Tab halaman Rekap (Super Admin): Penjualan | Alumunium | Proyek | Pengeluaran.
     Ditaruh di antara header halaman dan tabel (gaya Filament, x-fi.tabs:
     tab bergaris bawah, rata kiri), sama seperti x-finance.invoice-tabs.

     Props:
     - tabs   : [key => ['label' => ..., 'icon' => ...]] dari RekapController
                (ikon Font Awesome bawaan controller diganti Heroicons di sini)
     - active : key tab yang sedang aktif --}}
@props(['tabs' => [], 'active'])

@php
    $icons = [
        'sales' => 'chart-bar',
        'aluminium' => 'document-currency-dollar',
        'proyek' => 'building-office-2',
        'pengeluaran' => 'banknotes',
    ];

    $items = collect($tabs)->map(fn ($meta, $key) => [
        'label' => $meta['label'],
        'icon' => $icons[$key] ?? null,
        'url' => route('rekap.index', ['tab' => $key]),
    ])->all();
@endphp

<x-fi.tabs :active="$active" :tabs="$items" />
