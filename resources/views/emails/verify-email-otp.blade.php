<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi Email – MedScribe AI</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #20294A; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #FFFFFF; padding: 24px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border: 1px solid #E4E7F4; border-radius: 12px; padding: 32px 28px; text-align: left;">
                    <!-- Brand Header -->
                    <tr>
                        <td style="padding-bottom: 24px; border-bottom: 1px solid #EEEEFD;">
                            <div style="font-size: 20px; font-weight: 700; color: #122056; letter-spacing: -0.3px;">MedScribe AI</div>
                            <div style="font-size: 12px; color: #69718B; margin-top: 2px;">Sistem Informasi Rekam Medis Digital</div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding-top: 24px; font-size: 15px; line-height: 1.6; color: #20294A;">
                            <p style="margin: 0 0 16px 0;">Yth. Pengguna MedScribe AI,</p>
                            
                            <p style="margin: 0 0 20px 0;">Terima kasih telah melakukan pendaftaran akun di MedScribe AI.</p>
                            
                            <p style="margin: 0 0 16px 0;">Untuk menyelesaikan proses pendaftaran, silakan gunakan kode verifikasi berikut:</p>

                            <!-- OTP Container -->
                            <div style="background-color: #EEEEFD; border: 1px solid #5865DC; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0;">
                                <div style="font-family: 'Courier New', Courier, monospace; font-size: 34px; font-weight: 700; letter-spacing: 8px; color: #122056; line-height: 1.2;">
                                    {{ $otp }}
                                </div>
                                <div style="font-size: 13px; color: #5865DC; font-weight: 500; margin-top: 8px;">
                                    Kode verifikasi ini berlaku selama {{ $expiresInMinutes }} menit sejak email diterbitkan.
                                </div>
                            </div>

                            <p style="margin: 0 0 16px 0;">Masukkan kode tersebut pada halaman verifikasi email MedScribe AI untuk melanjutkan proses pendaftaran.</p>

                            <p style="margin: 0 0 28px 0; font-size: 14px; color: #69718B;">Apabila Anda tidak merasa melakukan pendaftaran ini, silakan abaikan email ini. Tidak diperlukan tindakan lebih lanjut.</p>

                            <!-- Signature -->
                            <div style="border-top: 1px solid #EEEEFD; padding-top: 20px; font-size: 14px; color: #20294A;">
                                <div style="margin-bottom: 4px;">Hormat kami,</div>
                                <div style="font-weight: 700; color: #122056;">Tim MedScribe AI</div>
                                <div style="font-size: 12px; color: #69718B;">Sistem Informasi Rekam Medis Digital</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
