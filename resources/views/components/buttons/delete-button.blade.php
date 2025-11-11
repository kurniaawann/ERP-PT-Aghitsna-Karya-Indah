@props(['modalId' => 'deleteModal'])

<button type="button" onclick="openModal('{{ $modalId }}')"
    class="w-full sm:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium">
    <i class="fa-solid fa-trash w-4 h-4"></i>
    <span>Hapus</span>
</button>
