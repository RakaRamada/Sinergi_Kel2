<?php
// File: app/controllers/VerifController.php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../../config/koneksi.php';

class VerifController
{
    private $conn;
    private $userModel;

    public function __construct($conn)
    {
        $this->conn = $conn;
        // Inisialisasi Model
        $this->userModel = new UserModel($conn);
    }

    public function verifyEmail()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (isset($_GET['code'])) {
            $token = $_GET['code'];

            // Panggil fungsi di UserModel (OOP Murni)
            $result = $this->userModel->verifyUserByToken($token);

            // Tampilkan Pesan Sesuai Hasil
            switch ($result) {
                case 'success':
                    $this->tampilkanPesan('Verifikasi Berhasil!', 'Akun Anda telah berhasil diverifikasi. Silakan login.', 'sukses');
                    break;
                case 'already_verified':
                    $this->tampilkanPesan('Sudah Diverifikasi', 'Akun ini sudah pernah diverifikasi sebelumnya.', 'info');
                    break;
                case 'invalid_or_expired':
                    $this->tampilkanPesan('Kode Tidak Valid', 'Kode verifikasi ini tidak valid atau tidak ditemukan.', 'gagal');
                    break;
                case 'db_error':
                    $this->tampilkanPesan('Terjadi Kesalahan', 'Gagal update database.', 'gagal');
                    break;
                default:
                    $this->tampilkanPesan('Gagal', 'Terjadi kesalahan tak terduga.', 'gagal');
                    break;
            }
        } else {
            $this->tampilkanPesan('Link Error', 'Link verifikasi tidak lengkap.', 'gagal');
        }
    }

    private function tampilkanPesan($judul, $pesan, $status = 'info')
    {
        // ... (Isi HTML sama persis dengan yang kamu kirim, tidak perlu diubah)
        // Copy bagian HTML function tampilkanPesan dari file lamamu ke sini
        $logo_path = 'public/assets/images/logo.png';
        
        // Ikon logic
        $icon_svg = '';
        if ($status == 'sukses') {
            $icon_svg = '<svg width="64" height="64" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#28a745"/></svg>';
        } elseif ($status == 'gagal') {
            $icon_svg = '<svg width="64" height="64" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#dc3545"/></svg>';
        } else {
            $icon_svg = '<svg width="64" height="64" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="#007bff"/></svg>';
        }

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>$judul</title>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
            <style>
                body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #495057; }
                .container { background-color: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; max-width: 480px; width: 90%; }
                .logo-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 24px; }
                .logo-container img { width: 50%; object-fit: cover; }
                .brand-name { font-size: 28px; font-weight: 700; color: #343a40; margin-top: 12px; }
                .icon-container { margin-bottom: 20px; }
                h2 { font-size: 24px; font-weight: 600; color: #212529; margin: 0 0 16px 0; }
                .message-box { height: 120px; display: flex; align-items: center; justify-content: center; }
                p { font-size: 16px; line-height: 1.7; margin-bottom: 0; }
                .footer { margin-top: 30px; font-size: 14px; color: #adb5bd; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="logo-container">
                    <img src="$logo_path" alt="Logo Sinergi">
                    <span class="brand-name">SINERGI</span>
                </div>
                <div class="icon-container">$icon_svg</div>
                <h2>$judul</h2>
                <div class="message-box"><p>$pesan</p></div>
                <p class="footer">Anda bisa menutup tab ini sekarang.<br><a href="index.php?page=login">Kembali ke Login</a></p>
            </div>
        </body>
        </html>
HTML;
        exit();
    }

    // =====================================
    // OTP VERIFICATION (NEW MODERN SYSTEM)
    // =====================================

    /**
     * Show OTP verification form
     */
    public function showOtpForm() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Check if email exists in session
        if (!isset($_SESSION['otp_email'])) {
            $_SESSION['error_message'] = "Sesi verifikasi tidak ditemukan. Silakan registrasi ulang.";
            header("Location: index.php?page=register");
            exit();
        }

        $email = $_SESSION['otp_email'];
        $nama = $_SESSION['otp_nama'] ?? 'User';
        $pesan = $_SESSION['otp_message'] ?? '';
        $pesan_type = $_SESSION['otp_message_type'] ?? 'error';
        unset($_SESSION['otp_message'], $_SESSION['otp_message_type']);

        // Mask email for display
        $maskedEmail = $this->maskEmail($email);

        require_once 'app/views/verify_otp.php';
    }

    /**
     * Process OTP verification
     */
    public function verifyOtp() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $otp = '';
        // Combine all 6 digit inputs
        for ($i = 1; $i <= 6; $i++) {
            $otp .= $_POST["otp{$i}"] ?? '';
        }

        $email = $_SESSION['otp_email'] ?? '';

        if (empty($email)) {
            $_SESSION['error_message'] = "Sesi verifikasi tidak ditemukan.";
            header("Location: index.php?page=register");
            exit();
        }

        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $_SESSION['otp_message'] = "Kode OTP harus 6 digit angka.";
            $_SESSION['otp_message_type'] = 'error';
            header("Location: index.php?page=verify-otp");
            exit();
        }

        // Verify OTP using UserModel
        $result = $this->userModel->verifyOtp($email, $otp);

        switch ($result) {
            case 'success':
                // Clear OTP session
                unset($_SESSION['otp_email'], $_SESSION['otp_nama']);
                $_SESSION['error_message'] = "🎉 Akun berhasil diverifikasi! Silakan login.";
                header("Location: index.php?page=login");
                exit();

            case 'already_verified':
                unset($_SESSION['otp_email'], $_SESSION['otp_nama']);
                $_SESSION['error_message'] = "Akun sudah diverifikasi sebelumnya. Silakan login.";
                header("Location: index.php?page=login");
                exit();

            case 'invalid_otp':
                $_SESSION['otp_message'] = "Kode OTP salah. Silakan coba lagi.";
                $_SESSION['otp_message_type'] = 'error';
                header("Location: index.php?page=verify-otp");
                exit();

            case 'otp_expired':
                $_SESSION['otp_message'] = "Kode OTP sudah expired. Silakan kirim ulang OTP.";
                $_SESSION['otp_message_type'] = 'warning';
                header("Location: index.php?page=verify-otp");
                exit();

            case 'user_not_found':
                $_SESSION['error_message'] = "User tidak ditemukan.";
                header("Location: index.php?page=register");
                exit();

            default:
                $_SESSION['otp_message'] = "Terjadi kesalahan. Silakan coba lagi.";
                $_SESSION['otp_message_type'] = 'error';
                header("Location: index.php?page=verify-otp");
                exit();
        }
    }

    /**
     * Resend OTP code
     */
    public function resendOtp() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $email = $_SESSION['otp_email'] ?? '';
        $nama = $_SESSION['otp_nama'] ?? 'User';

        if (empty($email)) {
            $_SESSION['error_message'] = "Sesi verifikasi tidak ditemukan.";
            header("Location: index.php?page=register");
            exit();
        }

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Update OTP in database
        if (!$this->userModel->setOtp($email, $otp)) {
            $_SESSION['otp_message'] = "Gagal mengirim ulang OTP. Coba lagi.";
            $_SESSION['otp_message_type'] = 'error';
            header("Location: index.php?page=verify-otp");
            exit();
        }

        // Send new OTP email
        $this->sendOtpEmailResend($email, $nama, $otp);

        $_SESSION['otp_message'] = "Kode OTP baru telah dikirim ke email Anda.";
        $_SESSION['otp_message_type'] = 'success';
        header("Location: index.php?page=verify-otp");
        exit();
    }

    /**
     * Send OTP email for resend
     */
    private function sendOtpEmailResend($email, $nama, $otp) {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sinergi.tik24@gmail.com';
            $mail->Password   = 'jzqzzlotalnaqqda';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('sinergi.tik24@gmail.com', 'PBL SINERGI');
            $mail->addAddress($email, $nama);
            $mail->isHTML(true);
            $mail->Subject = 'Kode OTP Baru - SINERGI';
            $mail->CharSet = 'UTF-8';
            
            $digits = str_split($otp);
            $otpBoxes = '';
            foreach ($digits as $digit) {
                $otpBoxes .= "<span style='display:inline-block;width:48px;height:56px;background:#111827;color:#fff;font-size:28px;font-weight:700;line-height:56px;text-align:center;border-radius:10px;margin:0 4px;font-family:monospace;'>{$digit}</span>";
            }
            
            $mail->Body = "
            <div style='font-family: Plus Jakarta Sans, -apple-system, sans-serif; max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden;'>
                <div style='background: linear-gradient(135deg, #111827 0%, #1f2937 100%); padding: 32px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>SINERGI</h1>
                    <p style='color: #9ca3af; margin: 8px 0 0 0; font-size: 14px;'>Kode OTP Baru</p>
                </div>
                <div style='padding: 40px 32px;'>
                    <p style='color: #374151; font-size: 16px;'>Halo <strong>{$nama}</strong>,</p>
                    <p style='color: #6b7280; font-size: 15px;'>Berikut adalah kode OTP baru Anda:</p>
                    <div style='text-align: center; margin: 32px 0;'>{$otpBoxes}</div>
                    <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px;'>
                        <p style='color: #92400e; font-size: 14px; margin: 0;'><strong>Kode berlaku 5 menit</strong></p>
                    </div>
                </div>
                <div style='background: #f9fafb; padding: 24px 32px; text-align: center; border-top: 1px solid #e5e7eb;'>
                    <p style='color: #9ca3af; font-size: 12px; margin: 0;'>&copy; 2025 Sinergi Dev Team</p>
                </div>
            </div>";

            $mail->send();
        } catch (Exception $e) {
            // Silent fail - message already set
        }
    }

    /**
     * Mask email for privacy (ra***@example.com)
     */
    private function maskEmail($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        
        $name = $parts[0];
        $domain = $parts[1];
        
        if (strlen($name) <= 2) {
            $masked = $name[0] . '***';
        } else {
            $masked = substr($name, 0, 2) . '***';
        }
        
        return $masked . '@' . $domain;
    }
}
?>