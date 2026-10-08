{{-- Tab Penawaran Harga (Super Admin): Penawaran Alumunium | Penawaran Proyek.
     Menggantikan submenu Penawaran Harga di sidebar (revisi klien: seperti Surat Menyurat). --}}
@props(['active'])

<x-tab-nav :active="$active" :tabs="[
    'alumunium' => ['label' => 'Penawaran Alumunium', 'icon' => 'fa-window-maximize', 'url' => route('aluminium-quotation.index')],
    'proyek' => ['label' => 'Penawaran Proyek', 'icon' => 'fa-clipboard-list', 'url' => route('project-quotation.index')],
]" />
