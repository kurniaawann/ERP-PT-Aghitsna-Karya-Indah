@props(['modalId' => 'addModal', 'text' => 'Tambah Data'])

<button type="button" onclick="openModal('{{ $modalId }}')"
    class="w-full lg:w-auto flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-3.5 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light transition-colors duration-200">
    <i class="fa-solid fa-plus"></i>
    <span>{{ $text }}</span>
</button>
