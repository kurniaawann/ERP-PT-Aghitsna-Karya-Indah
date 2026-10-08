{{-- =====================================================================
     KOP SURAT NOTA - dipakai bersama oleh layout proyek & sewa/jual
     PT Aghitsna Karya Indah

     - Kiri  : logo perusahaan (diperbesar agar jelas)
     - Tengah: nama perusahaan 12pt, lalu tagline & alamat 11pt
     - Garis ganda di bawah kop

     Style ada di nota-pdf.blade.php (scope .nota).
     ===================================================================== --}}

<table class="kop">
    <tr>
        <td class="kop-logo-cell">
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
