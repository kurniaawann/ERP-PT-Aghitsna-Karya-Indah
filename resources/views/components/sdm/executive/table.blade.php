{{-- ═══════════════════════════════════════════════════════════════════════════
     KOMPONEN TABEL PENANDATANGAN (gaya Filament)
     Toolbar (hapus massal kiri, pencarian kanan) + tabel + paginasi dalam
     satu kartu. Tanda tangan bisa diklik untuk pratinjau (openSignature).

     Hook JS (resources/js/pages/sdm/executive/index.js): #selectAll,
     input[name="ids[]"], #delete-button, form#deleteForm.
     Parameter: $executives (paginator Executive)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    $indexUrl = route('executive.index');
    $isFiltered = request()->filled('search');
@endphp

<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=ids]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=ids]:checked').length">

    {{-- ─── Toolbar: aksi massal (kiri) + pencarian (kanan) ──────────────────── --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 sm:px-6">
        <div x-cloak x-show="selected > 0" class="flex flex-wrap items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> data dipilih
            </span>
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        <form method="GET" action="{{ $indexUrl }}"
            class="flex w-full flex-wrap items-center gap-3 sm:ms-auto sm:w-auto sm:justify-end">
            <x-fi.search-input :value="request('search')" placeholder="Cari nama / jabatan..." class="w-full sm:w-72" />

            @if ($isFiltered)
                <x-fi.link :href="$indexUrl" color="gray" icon="x-mark" class="whitespace-nowrap"
                    title="Hapus pencarian">
                    Reset
                </x-fi.link>
            @endif
        </form>
    </div>

    {{-- ─── Form Hapus Massal + Tabel ────────────────────────────────────────── --}}
    <form id="deleteForm" method="POST" action="{{ route('executive.destroy') }}">
        @csrf
        @method('DELETE')

        <div class="relative overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-3 py-3.5 sm:ps-6">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th>Nama</x-fi.table.th>
                        <x-fi.table.th>Jabatan</x-fi.table.th>
                        <x-fi.table.th align="center">Tanda Tangan</x-fi.table.th>
                        <x-fi.table.th align="center" class="w-1">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($executives as $executive)
                        <tr class="bg-white transition duration-75 hover:bg-gray-50 has-[:checked]:bg-blue-50">
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="ids[]" value="{{ $executive->id }}" class="fi-checkbox"
                                    title="Pilih {{ $executive->name }}">
                            </td>
                            <x-fi.table.td class="min-w-[10rem] whitespace-normal font-medium">{{ $executive->name }}</x-fi.table.td>
                            <x-fi.table.td class="min-w-[8rem] whitespace-normal text-gray-700">{{ $executive->position ?? '-' }}</x-fi.table.td>

                            {{-- Tanda Tangan (klik untuk perbesar) --}}
                            <x-fi.table.td align="center">
                                @if ($executive->signature_image)
                                    <button type="button" title="Klik untuk perbesar"
                                        onclick="openSignature(@js(asset('storage/' . $executive->signature_image)), @js($executive->name))"
                                        class="inline-flex rounded-lg bg-white p-1 ring-1 ring-gray-950/10 transition hover:ring-primary">
                                        <img src="{{ asset('storage/' . $executive->signature_image) }}"
                                            alt="Tanda tangan {{ $executive->name }}"
                                            class="h-10 w-auto max-w-[6rem] object-contain">
                                    </button>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $executive->name }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square"
                                            onclick="openModal('editModal-{{ $executive->id }}')">
                                            Edit
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            :data-delete-row="$executive->id" :data-delete-row-label="$executive->name">
                                            Hapus
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                @if ($isFiltered)
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        description="Tidak ada penandatangan yang cocok dengan pencarian.">
                                        <x-fi.button color="gray" icon="x-mark" :href="$indexUrl">Reset pencarian</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="pencil-square" heading="Data tidak ditemukan" />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- ─── Paginasi ─────────────────────────────────────────────────────────── --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$executives" />
    </div>
</x-fi.card>
