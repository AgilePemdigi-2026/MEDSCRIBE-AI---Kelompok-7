<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Mail\VerifyEmailOtpMail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmailDeliverabilityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 1. Email verifikasi pendaftaran memiliki template HTML dan Plain-Text yang valid,
     * subjek profesional, header deliverability, serta tidak mengandung kata spam atau emoji.
     */
    public function test_email_verifikasi_pendaftaran_memiliki_format_dan_konten_profesional(): void
    {
        $otp = '582914';
        $mailable = new VerifyEmailOtpMail($otp, 'dr. Budi Setiawan', 10);

        // A. Verifikasi Subjek Resmi
        $envelope = $mailable->envelope();
        $this->assertEquals('Kode Verifikasi Email – MedScribe AI', $envelope->subject);

        // B. Verifikasi Header Anti-Loop / Anti-Spam
        $headers = $mailable->headers();
        $this->assertEquals('auto-generated', $headers->text['Auto-Submitted']);
        $this->assertEquals('All', $headers->text['X-Auto-Response-Suppress']);

        // C. Verifikasi Reply-To
        $this->assertNotEmpty($envelope->replyTo);

        // D. Verifikasi Render HTML
        $htmlContent = $mailable->render();
        $this->assertStringContainsString('Yth. Pengguna MedScribe AI,', $htmlContent);
        $this->assertStringContainsString('Terima kasih telah melakukan pendaftaran akun di MedScribe AI.', $htmlContent);
        $this->assertStringContainsString('Untuk menyelesaikan proses pendaftaran, silakan gunakan kode verifikasi berikut:', $htmlContent);
        $this->assertStringContainsString($otp, $htmlContent);
        $this->assertStringContainsString('Kode verifikasi ini berlaku selama 10 menit sejak email diterbitkan.', $htmlContent);
        $this->assertStringContainsString('Masukkan kode tersebut pada halaman verifikasi email MedScribe AI untuk melanjutkan proses pendaftaran.', $htmlContent);
        $this->assertStringContainsString('Apabila Anda tidak merasa melakukan pendaftaran ini, silakan abaikan email ini. Tidak diperlukan tindakan lebih lanjut.', $htmlContent);
        $this->assertStringContainsString('Tim MedScribe AI', $htmlContent);
        $this->assertStringContainsString('Sistem Informasi Rekam Medis Digital', $htmlContent);

        // Brand Palette & Styling
        $this->assertStringContainsString('#122056', $htmlContent); // Deep Navy
        $this->assertStringContainsString('#5865DC', $htmlContent); // Royal Indigo
        $this->assertStringContainsString('#EEEEFD', $htmlContent); // Pale Lavender

        // Bebas dari kata spam dan emoji mencurigakan
        $this->assertStringNotContainsString('⏱️', $htmlContent);
        $this->assertStringNotContainsString('URGENT', $htmlContent);
        $this->assertStringNotContainsString('SELAMAT', $htmlContent);
        $this->assertStringNotContainsString('Klik sekarang juga', $htmlContent);

        // E. Verifikasi Render Plain-Text (Multipart/Alternative)
        $textContent = view('emails.verify-email-otp-text', [
            'otp' => $otp,
            'userName' => 'dr. Budi Setiawan',
            'expiresInMinutes' => 10,
        ])->render();

        $this->assertStringContainsString('Yth. Pengguna MedScribe AI,', $textContent);
        $this->assertStringContainsString($otp, $textContent);
        $this->assertStringContainsString('Kode verifikasi ini berlaku selama 10 menit sejak email diterbitkan.', $textContent);
        $this->assertStringContainsString('Tim MedScribe AI', $textContent);
    }

    /**
     * 2. Email pemulihan kata sandi memiliki template HTML dan Plain-Text yang valid,
     * subjek profesional, header deliverability, serta instruksi keamanan yang tepat.
     */
    public function test_email_reset_password_memiliki_format_dan_konten_profesional(): void
    {
        $otp = '749103';
        $mailable = new PasswordResetOtpMail($otp, 'dr. Siti Rahma', 10);

        // A. Verifikasi Subjek Resmi
        $envelope = $mailable->envelope();
        $this->assertEquals('Kode Verifikasi Reset Password – MedScribe AI', $envelope->subject);

        // B. Verifikasi Header Anti-Loop / Anti-Spam
        $headers = $mailable->headers();
        $this->assertEquals('auto-generated', $headers->text['Auto-Submitted']);
        $this->assertEquals('All', $headers->text['X-Auto-Response-Suppress']);

        // C. Verifikasi Reply-To
        $this->assertNotEmpty($envelope->replyTo);

        // D. Verifikasi Render HTML
        $htmlContent = $mailable->render();
        $this->assertStringContainsString('Yth. Pengguna MedScribe AI,', $htmlContent);
        $this->assertStringContainsString('Kami menerima permintaan untuk mengatur ulang kata sandi akun MedScribe AI Anda.', $htmlContent);
        $this->assertStringContainsString('Untuk melanjutkan proses tersebut, silakan masukkan kode verifikasi berikut:', $htmlContent);
        $this->assertStringContainsString($otp, $htmlContent);
        $this->assertStringContainsString('Kode verifikasi berlaku selama 10 menit sejak email diterbitkan.', $htmlContent);
        $this->assertStringContainsString('Apabila Anda tidak merasa melakukan permintaan pengaturan ulang kata sandi, silakan abaikan email ini.', $htmlContent);
        $this->assertStringContainsString('Demi keamanan akun, jangan berikan kode verifikasi ini kepada siapa pun.', $htmlContent);
        $this->assertStringContainsString('Tim MedScribe AI', $htmlContent);
        $this->assertStringContainsString('Sistem Informasi Rekam Medis Digital', $htmlContent);

        // Brand Palette
        $this->assertStringContainsString('#122056', $htmlContent);
        $this->assertStringContainsString('#5865DC', $htmlContent);
        $this->assertStringContainsString('#EEEEFD', $htmlContent);

        // Bebas dari kata spam dan emoji
        $this->assertStringNotContainsString('⏱️', $htmlContent);
        $this->assertStringNotContainsString('URGENT', $htmlContent);

        // E. Verifikasi Render Plain-Text (Multipart/Alternative)
        $textContent = view('emails.password-reset-otp-text', [
            'otp' => $otp,
            'userName' => 'dr. Siti Rahma',
            'expiresInMinutes' => 10,
        ])->render();

        $this->assertStringContainsString('Yth. Pengguna MedScribe AI,', $textContent);
        $this->assertStringContainsString($otp, $textContent);
        $this->assertStringContainsString('Kode verifikasi berlaku selama 10 menit sejak email diterbitkan.', $textContent);
        $this->assertStringContainsString('Demi keamanan akun, jangan berikan kode verifikasi ini kepada siapa pun.', $textContent);
        $this->assertStringContainsString('Tim MedScribe AI', $textContent);
    }
}

