{{-- Tab Tunjangan & Potongan (Admin): Lembur | Kasbon.
     Menggantikan submenu Lembur/Kasbon di sidebar role admin (revisi klien:
     menu "Tunjangan & Potongan" langsung membuka halaman bertab). --}}
@props(['active'])

<x-tab-nav :active="$active" :tabs="[
    'overtime' => ['label' => 'Lembur', 'icon' => 'fa-clock', 'url' => route('overtime.index')],
    'kasbon' => ['label' => 'Kasbon', 'icon' => 'fa-hand-holding-usd', 'url' => route('kasbon.index')],
]" />
