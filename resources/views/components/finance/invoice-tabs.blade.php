{{-- Tab Invoice (Super Admin): Invoice Barang | Invoice Alumunium | Invoice Proyek.
     Menggantikan submenu Invoice di sidebar (revisi klien: seperti Surat Menyurat). --}}
@props(['active'])

<x-tab-nav :active="$active" :tabs="[
    'barang' => ['label' => 'Invoice Barang', 'icon' => 'fa-boxes-stacked', 'url' => route('item-invoice.index')],
    'alumunium' => ['label' => 'Invoice Alumunium', 'icon' => 'fa-window-maximize', 'url' => route('alumunium-invoice.index')],
    'proyek' => ['label' => 'Invoice Proyek', 'icon' => 'fa-file-contract', 'url' => route('proyek-invoice.index')],
]" />
