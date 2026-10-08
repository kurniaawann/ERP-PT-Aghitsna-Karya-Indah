{{-- =====================================================================
     Bilah tab bergaya halaman Surat Menyurat (dipakai Invoice & Penawaran
     Harga role Super Admin: satu menu sidebar → beberapa tab).
     Setiap tab adalah link ke halaman modulnya sendiri, sehingga route,
     redirect, dan JS tiap modul tetap seperti semula.

     Props:
     - tabs   : [key => ['label' => ..., 'icon' => 'fa-...', 'url' => ...]]
     - active : key tab yang sedang aktif
     ===================================================================== --}}
@props(['tabs' => [], 'active' => null])

@php
    // Kelas grid ditulis lengkap agar terdeteksi Tailwind (tidak dirakit dinamis)
    $gridClass = match (count($tabs)) {
        2 => 'sm:grid sm:grid-cols-2',
        3 => 'sm:grid sm:grid-cols-3',
        4 => 'sm:grid sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
    };
@endphp

<div class="bg-surface-base rounded-xl shadow-sm p-1.5 mb-4">
    <div class="flex flex-wrap gap-1.5 {{ $gridClass }}">
        @foreach ($tabs as $key => $meta)
            <a href="{{ $meta['url'] }}"
                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 border-2
                    {{ $active === $key
                        ? 'bg-primary text-white border-primary shadow'
                        : 'bg-surface-base text-text-primary border-transparent hover:bg-primary-light hover:text-primary' }}">
                <i class="fa-solid {{ $meta['icon'] }}"></i>
                <span>{{ $meta['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
