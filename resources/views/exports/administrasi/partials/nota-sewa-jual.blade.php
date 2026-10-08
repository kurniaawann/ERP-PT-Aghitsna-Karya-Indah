{{-- =====================================================================
     NOTA PDF - LAYOUT SEWA/JUAL (tipe_nota = sewa_jual)
     PT Aghitsna Karya Indah

     - Kop: partials/nota-kop (logo & kop yang sama dengan nota proyek)
     - Judul "NOTA"
     - Info kiri: No. Nota, Faktur No. & SJ No. (opsional), Periode (opsional)
     - Info kanan: tempat/tanggal + Kepada Yth. (rata kanan)
     - Tabel: NO / BANYAKNYA / NAMA BARANG / HARGA SATUAN / JUMLAH (minimal 7 baris)
     - Bawah kiri: rekening pembayaran + catatan
     - Bawah kanan: rincian Sewa/Jual, Ongkir, Bongkar, Lembur, Uang Jaminan,
       PPN (bila ada) & Jumlah -- kolom nilai sejajar dengan kolom Jumlah tabel
     - Tanda tangan: Penerima & Hormat Kami
     - Nominal ditulis "Rp. 50.000" via helper format_rupiah()

     Style bersama ada di nota-pdf.blade.php (scope .nota); style khusus
     layout ini di-scope ke .nota-sewa-jual.
     ===================================================================== --}}

<style>
    /* Bagian bawah dijaga tetap utuh (tidak terpotong antar halaman) */
    .nota-sewa-jual .bottom {
        width: 100%;
        border-collapse: collapse;
        page-break-inside: avoid;
        margin-top: -1px; /* Garis atas rincian menumpuk tepat di garis bawah tabel barang */
    }

    .nota-sewa-jual .bottom > tbody > tr > td,
    .nota-sewa-jual .bottom > tr > td {
        vertical-align: top;
        padding: 0;
    }

    /* Lebar kiri = No + Banyaknya + Nama Barang (5% + 17% + 34%) */
    .nota-sewa-jual .bottom-left {
        width: 56%;
        padding: 8px 14px 0 0 !important;
    }

    /* Lebar kanan = Harga Satuan + Jumlah (24% + 20%) */
    .nota-sewa-jual .bottom-right {
        width: 44%;
    }

    .nota-sewa-jual .bank-info {
        margin-bottom: 8px;
    }

    .nota-sewa-jual .footer-note {
        font-style: italic;
    }

    .nota-sewa-jual .summary {
        width: 100%;
        border-collapse: collapse;
    }

    .nota-sewa-jual .summary td {
        border: 1px solid #000;
        padding: 4px 6px;
        height: 24px;
        vertical-align: middle;
    }

    /* Kolom sejajar dengan kolom Harga Satuan (24/44) & Jumlah (20/44) tabel barang */
    .nota-sewa-jual .summary-label {
        width: 55.1%;
        white-space: nowrap;
    }

    .nota-sewa-jual .summary-value {
        width: 44.9%;
        text-align: center;
        white-space: nowrap;
    }

    .nota-sewa-jual .summary .total-row td {
        font-weight: bold;
        background: #e8e8e8;
    }
</style>

@php
    $items = $nota->items ?? [];
    $notaDate = \Carbon\Carbon::parse($nota->nota_date)->locale('id')->translatedFormat('d F Y');
    $hasPeriode = $nota->periode_start || $nota->periode_end;

    // Persentase PPN tanpa desimal nol berlebih (11.00 -> 11, 11.50 -> 11,5)
    $ppnLabel = format_persen($nota->ppn_percentage);

    // Rincian biaya tambahan (kosong ditampilkan "-")
    $summaryRows = [
        'Sewa / Jual' => $nota->sewa_jual,
        'Ongkos Kirim PP / 1x' => $nota->ongkos_kirim,
        'Bongkar / Pasang' => $nota->bongkar_pasang,
        'Lembur Antar / Ambil' => $nota->lembur,
        'Uang Jaminan' => $nota->uang_jaminan,
    ];
@endphp

<div class="nota nota-sewa-jual">

    <!-- KOP SURAT -->
    @include('exports.administrasi.partials.nota-kop')

    <!-- JUDUL -->
    <div class="doc-title"><span>NOTA</span></div>

    <!-- BLOK INFO -->
    <table class="info">
        <tr>
            <!-- KIRI: Nomor referensi & periode -->
            <td class="info-left">
                <table class="meta">
                    <tr>
                        <td class="meta-label">No. Nota</td>
                        <td class="meta-colon">:</td>
                        <td>{{ $nota->id_nota ?? '-' }}</td>
                    </tr>
                    @if (trim((string) $nota->faktur_no) !== '')
                        <tr>
                            <td class="meta-label">Faktur No.</td>
                            <td class="meta-colon">:</td>
                            <td>{{ $nota->faktur_no }}</td>
                        </tr>
                    @endif
                    @if (trim((string) $nota->sj_no) !== '')
                        <tr>
                            <td class="meta-label">SJ No.</td>
                            <td class="meta-colon">:</td>
                            <td>{{ $nota->sj_no }}</td>
                        </tr>
                    @endif
                    @if ($hasPeriode)
                        <tr>
                            <td class="meta-label">Periode</td>
                            <td class="meta-colon">:</td>
                            <td>
                                {{ $nota->periode_start ? \Carbon\Carbon::parse($nota->periode_start)->format('d/m/Y') : '-' }}
                                s/d
                                {{ $nota->periode_end ? \Carbon\Carbon::parse($nota->periode_end)->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @endif
                </table>
            </td>

            <!-- KANAN: Tempat/Tanggal & Kepada Yth. -->
            <td class="info-right">
                <div class="place-date">
                    {{ trim((string) $nota->location) !== '' ? $nota->location . ', ' : '' }}{{ $notaDate }}
                </div>
                <div class="kepada-label">Kepada Yth.</div>
                <div class="kepada-value">{{ $nota->kepada ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <!-- TABEL BARANG -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 17%;">Banyaknya</th>
                <th style="width: 34%;">Nama Barang</th>
                <th style="width: 24%;">Harga Satuan</th>
                <th style="width: 20%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $item['banyaknya'] ?? '' }}</td>
                    <td>{{ $item['nama_barang'] ?? '' }}</td>
                    <td class="text-center nowrap">{{ format_rupiah($item['harga_satuan'] ?? 0) }}</td>
                    <td class="text-center nowrap">{{ format_rupiah($item['jumlah'] ?? 0) }}</td>
                </tr>
            @endforeach

            <!-- Baris kosong pelengkap grid (minimal 7 baris) -->
            @for ($i = count($items); $i < 7; $i++)
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor

        </tbody>
    </table>

    <!-- BAGIAN BAWAH: Rekening & catatan (kiri), rincian biaya (kanan) -->
    <table class="bottom">
        <tr>
            <td class="bottom-left">
                @php $banks = $nota->paymentAccounts(); @endphp
                @if ($banks && $banks->count() > 0)
                    <div class="bank-info">
                        @foreach ($banks as $bank)
                            <div>Rek. {{ $bank->bank_name }} : {{ $bank->account_number }} a/n {{ $bank->account_holder }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="footer-note">
                    *) Faktur dianggap lunas setelah dana kami terima
                    tunai atau telah ditransfer ke rekening kami.
                </div>
            </td>

            <td class="bottom-right">
                <table class="summary">
                    @foreach ($summaryRows as $label => $value)
                        <tr>
                            <td class="summary-label">{{ $label }}</td>
                            <td class="summary-value">{{ $value ? format_rupiah($value) : '-' }}</td>
                        </tr>
                    @endforeach
                    @if ($nota->ppn_percentage > 0)
                        <tr>
                            <td class="summary-label">PPN ({{ $ppnLabel }}%)</td>
                            <td class="summary-value">{{ format_rupiah($nota->ppn_amount) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td class="summary-label">JUMLAH</td>
                        <td class="summary-value">{{ format_rupiah($nota->total_with_ppn) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN -->
    <table class="sign">
        <tr>
            <td class="sign-col">Penerima,</td>
            <td class="sign-gap"></td>
            <td class="sign-col">Hormat Kami,</td>
        </tr>
        <tr>
            <td class="sign-col sign-space"></td>
            <td class="sign-gap"></td>
            <td class="sign-col sign-space"></td>
        </tr>
        <tr>
            <td class="sign-col"><span class="sign-name">{{ $nota->penerima ?: "\u{00A0}" }}</span></td>
            <td class="sign-gap"></td>
            <td class="sign-col"><span class="sign-name">&nbsp;</span></td>
        </tr>
    </table>

</div>
