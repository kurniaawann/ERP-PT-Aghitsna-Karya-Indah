@props(['modalId' => 'deleteModal'])

<button type="button" id="delete-button" onclick="openModal('{{ $modalId }}')" disabled
    class="w-full sm:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-btn-delete">
    <i class="fa-solid fa-trash w-4 h-4"></i>
    <span>Hapus</span>
</button>
