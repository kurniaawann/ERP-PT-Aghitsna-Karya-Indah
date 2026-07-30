{{--
    Komponen Badge Status Pembayaran.

    Digunakan untuk menampilkan status Lunas/Belum Lunas
    pada tabel dan kartu di halaman laporan penjualan.

    @param string $status  Status pembayaran ('Lunas' atau 'Belum Lunas')
--}}
@props(['status'])

@if ($status === 'Lunas')
    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-success-light text-success">
        Lunas
    </span>
@else
    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-warning-light text-warning">
        Belum Lunas
    </span>
@endif
