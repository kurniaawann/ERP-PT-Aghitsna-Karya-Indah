<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password</title>
</head>

<body class="bg-surface-secondary text-text-primary"
    style="margin:0;padding:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;">
    {{-- Note: Email client sering tidak memproses Tailwind runtime, jadi kita tetap pakai inline style seminimal mungkin. --}}

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
        style="padding:24px 12px;background:#f4f6fb;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600"
                    style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.06);">

                    {{-- Header --}}
                    <tr>
                        <td class="px-6 py-5"
                            style="padding:22px 24px;background:linear-gradient(135deg,#2563eb,#1d4ed8);">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td class="text-white font-bold text-base"
                                        style="color:#ffffff;font-weight:700;font-size:16px;">
                                        ERP PT Aghitsna Karya Indah
                                    </td>
                                    <td align="right" class="text-white/90 text-xs"
                                        style="color:#ffffff;opacity:0.95;font-size:12.5px;">
                                        Notifikasi Keamanan
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="p-6" style="padding:26px 24px;">
                            <h2 class="text-text-primary text-2xl font-semibold"
                                style="margin:0;color:#0f172a;font-size:22px;line-height:1.3;">
                                Reset Password
                            </h2>

                            <p class="text-text-secondary mt-3"
                                style="margin:8px 0 0 0;color:#334155;font-size:14.5px;line-height:1.6;">
                                Halo <b>{{ $name }}</b>, kami menerima permintaan reset password untuk akun
                                Anda.
                            </p>

                            <div class="mt-4 p-4 rounded-xl border"
                                style="margin-top:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
                                <p class="text-text-primary font-bold text-sm"
                                    style="margin:0 0 6px 0;color:#0f172a;font-size:13.5px;font-weight:700;">
                                    Tindakan yang perlu dilakukan
                                </p>
                                <p class="text-slate-600 text-sm"
                                    style="margin:0;color:#475569;font-size:13.5px;line-height:1.6;">
                                    Klik tombol di bawah untuk membuat password baru. Link ini berlaku selama <b>60
                                        menit</b>.
                                </p>
                            </div>

                            <div class="mt-6 text-center" style="margin:22px auto 0;text-align:center;">
                                <a href="{{ $resetUrl }}"
                                    class="inline-block bg-primary text-white font-bold text-sm px-4 py-3 rounded-lg"
                                    style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 18px;border-radius:10px;">
                                    Buat Password Baru
                                </a>
                            </div>

                            <p class="mt-4" style="margin:18px 0 0 0;color:#64748b;font-size:12.5px;line-height:1.6;">
                                Jika Anda tidak meminta reset password, abaikan email ini.
                            </p>

                            <p class="mt-4" style="margin:16px 0 0 0;color:#94a3b8;font-size:12px;line-height:1.6;">
                                Keamanan akun Anda penting. Harap gunakan password yang kuat dan unik.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="px-6 py-5"
                            style="padding:18px 24px;background:#f8fafc;border-top:1px solid #eef2f7;">
                            <p class="text-slate-400" style="margin:0;color:#94a3b8;font-size:12.5px;line-height:1.6;">
                                Terima kasih,<br />
                                tim ERP PT Aghitsna Karya Indah
                            </p>
                        </td>
                    </tr>

                </table>

                <p class="mt-3" style="margin:14px 0 0 0;color:#94a3b8;font-size:11.5px;">
                    © {{ date('Y') }} ERP PT Aghitsna Karya Indah
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
