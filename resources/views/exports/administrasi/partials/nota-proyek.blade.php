{{-- =====================================================================
     NOTA PDF - LAYOUT PROYEK (tipe_nota = proyek)
     PT Aghitsna Karya Indah

     - Kop: logo + nama & alamat perusahaan, garis ganda
     - Judul "NOTA"
     - Info kiri: No. Nota, No. Invoice & No. DO (opsional), Proyek
     - Info kanan: tanggal + Kepada Yth.
     - Tabel: NO / QTY / SATUAN / NAMA BARANG / HARGA / JUMLAH (minimal 9 baris)
     - Baris total "JUMLAH" di bawah kolom Harga/Jumlah
     - Tanda tangan: Tanda Terima (penerima) & Hormat Kami (penandatangan + divisi)

     Style bersama ada di nota-pdf.blade.php (scope .nota).
     ===================================================================== --}}

@php
    $items = $nota->items ?? [];
    $rupiah = fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
    $notaDate = \Carbon\Carbon::parse($nota->nota_date)->locale('id')->translatedFormat('d F Y');
    $penandatangan = $nota->penandatangan ?? [];

    // Nama proyek ditampilkan terpisah hanya bila berbeda dengan "Kepada"
    // (nota dari invoice semen tanpa pengurus memakai nama proyek sebagai "Kepada").
    $showProyek = trim((string) $nota->nama_proyek) !== ''
        && trim((string) $nota->nama_proyek) !== trim((string) $nota->kepada);
@endphp

<div class="nota nota-proyek">

    <!-- KOP SURAT -->
    <table class="kop">
        <tr>
            <td class="kop-side">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. Aghitsna Karya Indah" class="kop-logo">
            </td>
            <td class="kop-text">
                <div class="kop-name">PT. AGHITSNA KARYA INDAH</div>
                <div class="kop-tagline">DESIGN AND BUILD</div>
                <div class="kop-address">
                    JL. TANAH BARU RAYA PERTIWI RT. 01/05<br>
                    BEJI, DEPOK, JAWA BARAT<br>
                    Telp. 021-29034923 - 0812.9596.552 &nbsp;|&nbsp; Email : Design@aghitsna.id
                </div>
            </td>
            <td class="kop-side"></td>
        </tr>
    </table>
    <div class="kop-rule"></div>

    <!-- JUDUL -->
    <div class="doc-title"><span>NOTA</span></div>

    <!-- BLOK INFO -->
    <table class="info">
        <tr>
            <!-- KIRI: Nomor referensi -->
            <td class="info-left">
                <table class="meta">
                    <tr>
                        <td class="meta-label">No. Nota</td>
                        <td class="meta-colon">:</td>
                        <td>{{ $nota->id_nota ?? '-' }}</td>
                    </tr>
                    @if (trim((string) $nota->invoice_number) !== '')
                        <tr>
                            <td class="meta-label">No. Invoice</td>
                            <td class="meta-colon">:</td>
                            <td>{{ $nota->invoice_number }}</td>
                        </tr>
                    @endif
                    @if (trim((string) $nota->do_no) !== '')
                        <tr>
                            <td class="meta-label">No. DO</td>
                            <td class="meta-colon">:</td>
                            <td>{{ $nota->do_no }}</td>
                        </tr>
                    @endif
                    @if ($showProyek)
                        <tr>
                            <td class="meta-label">Proyek</td>
                            <td class="meta-colon">:</td>
                            <td>{{ $nota->nama_proyek }}</td>
                        </tr>
                    @endif
                </table>
            </td>

            <!-- KANAN: Tanggal & Kepada Yth. -->
            <td class="info-right">
                <div class="place-date">{{ $notaDate }}</div>
                <div class="kepada-label">Kepada Yth.</div>
                <div class="kepada-value">{{ $nota->kepada ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <!-- TABEL BARANG -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 9%;">Qty</th>
                <th style="width: 11%;">Satuan</th>
                <th style="width: 38%;">Nama Barang</th>
                <th style="width: 18%;">Harga</th>
                <th style="width: 18%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $item['quantity'] ?? '' }}</td>
                    <td class="text-center">{{ $item['satuan'] ?? '' }}</td>
                    <td>{{ $item['nama_barang'] ?? '' }}</td>
                    <td class="text-center nowrap">{{ $rupiah($item['harga'] ?? 0) }}</td>
                    <td class="text-center nowrap">{{ $rupiah($item['jumlah'] ?? 0) }}</td>
                </tr>
            @endforeach

            <!-- Baris kosong pelengkap grid (minimal 9 baris) -->
            @for ($i = count($items); $i < 9; $i++)
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor

            <!-- Total -->
            <tr>
                <td class="total-blank" colspan="4"></td>
                <td class="total-label">JUMLAH</td>
                <td class="total-value nowrap">{{ $rupiah($nota->jumlah_total) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <table class="sign">
        <tr>
            <td class="sign-col">Tanda Terima,</td>
            <td class="sign-gap"></td>
            <td class="sign-col">Hormat Kami,</td>
        </tr>
        <tr>
            <td class="sign-col sign-space"></td>
            <td class="sign-gap"></td>
            <td class="sign-col sign-space">
                @if (!empty($penandatangan['signature_image']))
                    <img src="{{ storage_path('app/public/' . $penandatangan['signature_image']) }}"
                        alt="Tanda Tangan" class="sign-image">
                @endif
            </td>
        </tr>
        <tr>
            <td class="sign-col"><span class="sign-name">{{ $nota->penerima ?: "\u{00A0}" }}</span></td>
            <td class="sign-gap"></td>
            <td class="sign-col">
                <span class="sign-name">{{ ($penandatangan['name'] ?? null) ?: "\u{00A0}" }}</span>
                @if (!empty($penandatangan['divisi']))
                    <div>{{ $penandatangan['divisi'] }}</div>
                @endif
            </td>
        </tr>
    </table>

</div>
