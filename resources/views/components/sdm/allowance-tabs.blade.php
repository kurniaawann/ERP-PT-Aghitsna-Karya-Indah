{{-- Tab Tunjangan & Potongan (Admin): Lembur | Kasbon (gaya Filament).
     Menggantikan submenu Lembur/Kasbon di sidebar role admin (revisi klien:
     menu "Tunjangan & Potongan" langsung membuka halaman bertab). --}}
@props(['active'])

<x-fi.tabs :active="$active" :tabs="[
    'overtime' => ['label' => 'Lembur', 'icon' => 'clock', 'url' => route('overtime.index')],
    'kasbon' => ['label' => 'Kasbon', 'icon' => 'banknotes', 'url' => route('kasbon.index')],
]" />
