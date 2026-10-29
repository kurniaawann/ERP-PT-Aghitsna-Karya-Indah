{{-- Tabel Rekening Pembayaran (gaya Filament): toolbar (hapus massal + pencarian),
     tabel, paginasi. Jumlah baris terpilih dihitung Alpine untuk menampilkan
     grup aksi massal ("x rekening dipilih" + tombol hapus); status disabled
     #delete-button tetap diatur payment-accounts/index.js.

     Hook JS (payment-accounts/index.js): form#deleteForm (+ token CSRF untuk
     toggleActive), #selectAll, .account-checkbox, #delete-button. --}}
<x-fi.card x-data="{ selected: 0 }"
    x-init="selected = $el.querySelectorAll('[name^=selected_accounts]:checked').length"
    x-on:change="selected = $el.querySelectorAll('[name^=selected_accounts]:checked').length">

    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        {{-- Aksi massal: hanya tampil saat ada baris yang dicentang, agar tidak
             tertukar dengan hapus satu rekening lewat menu ⋮ --}}
        <div x-cloak x-show="selected > 0" class="flex shrink-0 items-center gap-3">
            <span class="whitespace-nowrap text-sm text-gray-500">
                <span class="font-semibold text-gray-950" x-text="selected"></span> rekening dipilih
            </span>

            {{-- Tombol Hapus Massal (status disabled tetap diatur payment-accounts/index.js) --}}
            <x-fi.button id="delete-button" color="danger" size="sm" icon="trash" outlined disabled
                onclick="openModal('deleteModal')">
                Hapus yang dipilih
            </x-fi.button>
        </div>

        {{-- Form Pencarian: submit GET ke route('payment-accounts.index') dengan param 'search' (Enter). --}}
        <form method="GET" action="{{ route('payment-accounts.index') }}" class="flex w-full items-center gap-3 sm:ms-auto sm:max-w-sm">
            <x-fi.search-input :value="request('search')" placeholder="Cari rekening..." />

            @if (request()->filled('search'))
                <x-fi.link :href="route('payment-accounts.index')" color="gray" icon="x-mark" title="Hapus pencarian">Reset</x-fi.link>
            @endif
        </form>
    </div>

    <form id="deleteForm" method="POST" action="{{ route('payment-accounts.destroySelected') }}">
        @csrf
        @method('DELETE')

        <div class="overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 text-start">

                {{-- Header Tabel --}}
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1 px-3 py-3.5 sm:ps-6">
                            <input type="checkbox" id="selectAll" class="fi-checkbox" title="Pilih semua">
                        </th>
                        <x-fi.table.th>Nama Bank</x-fi.table.th>
                        <x-fi.table.th>Nomor Rekening</x-fi.table.th>
                        <x-fi.table.th>Nama Pemilik</x-fi.table.th>
                        <x-fi.table.th>Status</x-fi.table.th>
                        <x-fi.table.th align="center">Aksi</x-fi.table.th>
                    </tr>
                </thead>

                {{-- Body Tabel --}}
                <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                    @forelse ($accounts as $account)
                        <tr class="transition duration-75 hover:bg-gray-50 has-[:checked]:bg-primary-light/30">

                            {{-- Checkbox --}}
                            <td class="w-1 px-3 py-4 sm:ps-6">
                                <input type="checkbox" name="selected_accounts[]" value="{{ $account->id }}"
                                    class="fi-checkbox account-checkbox" title="Pilih {{ $account->bank_name }}">
                            </td>

                            {{-- Nama Bank --}}
                            <x-fi.table.td>
                                <div class="flex items-center gap-x-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary ring-1 ring-inset ring-blue-600/10">
                                        <x-fi.icon name="building-library" class="h-5 w-5" />
                                    </span>
                                    <span class="font-medium">{{ $account->bank_name }}</span>
                                </div>
                            </x-fi.table.td>

                            {{-- Nomor Rekening --}}
                            <x-fi.table.td>
                                <span class="rounded-md bg-gray-50 px-2 py-1 font-mono text-sm tabular-nums text-gray-950 ring-1 ring-inset ring-gray-950/10">
                                    {{ $account->account_number }}
                                </span>
                            </x-fi.table.td>

                            {{-- Nama Pemilik --}}
                            <x-fi.table.td class="min-w-[12rem] whitespace-normal text-gray-700">
                                {{ $account->account_holder }}
                            </x-fi.table.td>

                            {{-- Status: toggle switch → toggleActive(id) (index.js) --}}
                            <x-fi.table.td>
                                <button type="button" role="switch" aria-checked="{{ $account->is_active ? 'true' : 'false' }}"
                                    onclick="toggleActive({{ $account->id }})"
                                    title="{{ $account->is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan' }}"
                                    class="inline-flex items-center gap-x-2 rounded-full outline-none focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-offset-2">
                                    <span class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 {{ $account->is_active ? 'bg-emerald-500' : 'bg-gray-200' }}">
                                        <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 {{ $account->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </span>
                                    <span class="text-sm font-medium {{ $account->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                                        {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </x-fi.table.td>

                            {{-- Aksi: tombol ⋮ berisi menu aksi baris --}}
                            <x-fi.table.td align="center">
                                <x-fi.dropdown label="Aksi {{ $account->bank_name }}" class="-my-1.5">
                                    <x-fi.dropdown.list>
                                        <x-fi.dropdown.item icon="pencil-square" onclick="openModal('editModal-{{ $account->id }}')">
                                            Edit Rekening
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>

                                    {{-- Aksi destruktif dipisah di paling bawah --}}
                                    <x-fi.dropdown.list>
                                        {{-- Hapus rekening ini saja (modal x-fi.delete-row-modal) --}}
                                        <x-fi.dropdown.item icon="trash" color="danger"
                                            data-delete-row="{{ $account->id }}"
                                            data-delete-row-label="{{ $account->bank_name }} · {{ $account->account_number }}">
                                            Hapus Rekening
                                        </x-fi.dropdown.item>
                                    </x-fi.dropdown.list>
                                </x-fi.dropdown>
                            </x-fi.table.td>
                        </tr>
                    @empty
                        {{-- Pesan jika data kosong --}}
                        <tr>
                            <td colspan="6">
                                @if (request('search'))
                                    <x-fi.empty-state icon="magnifying-glass" heading="Data tidak ditemukan"
                                        :description="'Tidak ada rekening yang cocok dengan pencarian “' . request('search') . '”.'">
                                        <x-fi.button color="gray" icon="x-mark" :href="route('payment-accounts.index')">Hapus pencarian</x-fi.button>
                                    </x-fi.empty-state>
                                @else
                                    <x-fi.empty-state icon="building-library" heading="Belum ada rekening pembayaran"
                                        description="Klik “Tambah Rekening” untuk menambahkan rekening pertama." />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Paginasi (selalu tampil, termasuk saat hanya 1 halaman) --}}
    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
        <x-fi.pagination :paginator="$accounts" />
    </div>
</x-fi.card>
