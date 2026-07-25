@props(['modalId' => 'addModal', 'text' => 'Tambah Data', 'responsive' => 'xl'])

@if($responsive === 'custom')
<button type="button" onclick="openModal('{{ $modalId }}')"
    class="w-full min-[1530px]:w-auto flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-3.5 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light transition-colors duration-200">
@else
<button type="button" onclick="openModal('{{ $modalId }}')"
    class="w-full xl:w-auto flex items-center justify-center gap-2 rounded-lg bg-btn-add hover:bg-btn-add-hover px-4 py-3.5 text-sm font-medium text-white focus:outline-none focus:ring-4 focus:ring-success-light transition-colors duration-200">
@endif
    <i class="fa-solid fa-plus"></i>
    <span>{{ $text }}</span>
</button>
