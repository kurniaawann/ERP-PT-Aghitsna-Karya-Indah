{{-- Tab Invoice (Super Admin): Invoice Barang | Invoice Alumunium | Invoice Proyek.
     Menggantikan submenu Invoice di sidebar (revisi klien: seperti Surat Menyurat).
     Ditaruh di antara header halaman dan tabel (gaya Filament, x-fi.tabs). --}}
@props(['active'])

<x-fi.tabs :active="$active" :tabs="[
    'barang' => ['label' => 'Invoice Barang', 'icon' => 'cube', 'url' => route('item-invoice.index')],
    'alumunium' => ['label' => 'Invoice Alumunium', 'icon' => 'squares-2x2', 'url' => route('alumunium-invoice.index')],
    'proyek' => ['label' => 'Invoice Proyek', 'icon' => 'building-office-2', 'url' => route('proyek-invoice.index')],
]" />
