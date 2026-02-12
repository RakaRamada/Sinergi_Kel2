<?php
// File: app/controllers/AuthController.php

require_once __DIR__ . '/../models/UserModel.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController
{
    private $conn;
    private $userModel; // Tambahkan properti userModel

    public function __construct($conn)
    {
        $this->conn = $conn;
        // Inisialisasi UserModel sebagai Object
        $this->userModel = new UserModel($this->conn);
    }

    // --- FUNGSI LOGIN ---
    public function showLogin()
    {
        $pesan = $_SESSION['error_message'] ?? '';
        unset($_SESSION['error_message']);

        // Ambil data input lama agar user tidak ngetik ulang
        $old_email = $_SESSION['old_email'] ?? '';
        unset($_SESSION['old_email']); 

        require_once 'app/views/login.php';
    }

    public function doLogin()
    {
        $email = trim($_POST['email'] ?? '');
        $password_input = $_POST['password'] ?? '';
        $captcha_input = $_POST['captcha_code'] ?? '';
        $captcha_session = $_SESSION["code"] ?? '';

        // VALIDASI CAPTCHA
        if (empty($captcha_input) || $captcha_session != $captcha_input) {
            $_SESSION['error_message'] = "Kode CAPTCHA salah!";
            $_SESSION['old_email'] = $email; 
            $_SESSION['temp_pass'] = $password_input;
            header("Location: index.php?page=login");
            exit();
        }

        // VALIDASI INPUT KOSONG
        if (empty($email) || empty($password_input)) {
            $_SESSION['error_message'] = "Email dan password wajib diisi!";
            $_SESSION['old_email'] = $email;
            header("Location: index.php?page=login");
            exit();
        }

        // PERBAIKAN DISINI: Panggil method dari object userModel
        $user_data = $this->userModel->getUserByEmail($email);

        if ($user_data && isset($user_data['password']) && password_verify($password_input, $user_data['password'])) {
            // CEK BANNED: Gerbang keamanan - tolak user yang dibanned
            if ((int)($user_data['is_banned'] ?? 0) === 1) {
                $_SESSION['error_message'] = "Akun Anda telah dibekukan oleh Admin. Hubungi administrator untuk informasi lebih lanjut.";
                $_SESSION['old_email'] = $email;
                header("Location: index.php?page=login");
                exit();
            }
            
            if ((int)$user_data['is_verif'] === 1) {
                $_SESSION['user_id']      = $user_data['user_id'];
                $_SESSION['nama_lengkap'] = $user_data['nama_lengkap'];
                $_SESSION['username']     = $user_data['username'];
                $_SESSION['role_name']    = $user_data['role_name'] ?? 'Mahasiswa';
                $_SESSION['role_id']      = $user_data['role_id']; 
                // Normalize avatar_url to full path
                $raw_avatar = $user_data['avatar_url'] ?? '';
                if (empty($raw_avatar)) {
                    $_SESSION['avatar_url'] = '/Sinergi/public/assets/images/user.png';
                } elseif (strpos($raw_avatar, '/') !== false) {
                    $_SESSION['avatar_url'] = $raw_avatar; // Already full path
                } else {
                    $_SESSION['avatar_url'] = '/Sinergi/public/uploads/avatars/' . $raw_avatar;
                }

                header("Location: index.php?page=" . ($user_data['role_id'] == 5 ? "admin-dashboard" : "dashboard"));
                exit();
            } else {
                $_SESSION['error_message'] = "Akun belum diverifikasi. Cek email Anda.";
                $_SESSION['old_email'] = $email;
                header("Location: index.php?page=login");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Email atau Password salah!";
            $_SESSION['old_email'] = $email;
            header("Location: index.php?page=login");
            exit();
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header("Location: index.php?page=login");
        exit();
    }

    // =====================================
    // FUNGSI REGISTRASI
    // =====================================

    public function showRegister()
    {
        $pesan = $_GET['pesan'] ?? '';
        $old_data = []; 
        require_once 'app/views/register.php';
    }

    public function doRegister()
    {
        // 1. Ambil Data Input
        $role           = $_POST['role'] ?? '1';
        $username       = trim($_POST['username'] ?? '');
        $nama_lengkap   = trim($_POST['nama_lengkap'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $password_input = $_POST['password'] ?? '';
        $confirm_pass   = $_POST['confirm_password'] ?? '';
        $nomor_induk    = trim($_POST['nomor_induk'] ?? ''); 

        $input_data = $_POST; 

        // 2. VALIDASI INPUT KOSONG
        if (empty($username) || empty($nama_lengkap) || empty($email) || empty($password_input)) {
            $this->renderRegisterView("Semua field wajib diisi!", $input_data);
        }

        // 3. VALIDASI PASSWORD COMPLEXITY
        if (strlen($password_input) < 6) { 
            $this->renderRegisterView("Password minimal harus 6 karakter!", $input_data);
        }
        if (!preg_match('/[A-Z]/', $password_input)) {
            $this->renderRegisterView("Password harus mengandung minimal satu huruf kapital (A-Z)!", $input_data);
        }
        if (!preg_match('/[0-9]/', $password_input)) {
            $this->renderRegisterView("Password harus mengandung minimal satu angka!", $input_data);
        }
        if ($password_input !== $confirm_pass) {
            $this->renderRegisterView("Konfirmasi password tidak cocok!", $input_data);
        }

        // 4. VALIDASI ROLE & EMAIL KAMPUS
        if ($role == '1') { // Mahasiswa
            if (!strpos($email, '@stu.pnj.ac.id')) {
                $this->renderRegisterView("Gagal: Mahasiswa wajib menggunakan email @stu.pnj.ac.id", $input_data);
            }
            if (empty($nomor_induk)) {
                $this->renderRegisterView("Gagal: Mahasiswa wajib mengisi NIM", $input_data);
            }
            // TAMBAHAN: Validasi Hanya Angka
            if (!ctype_digit($nomor_induk)) {
                $this->renderRegisterView("Gagal: NIM harus berupa angka tanpa spasi/karakter lain", $input_data);
            }
        } 
            else if ($role == '2') { // Dosen
                if (!strpos($email, '@tik.pnj.ac.id')) {
                    $this->renderRegisterView("Gagal: Dosen wajib menggunakan email @tik.pnj.ac.id", $input_data);
                }
                if (empty($nomor_induk)) {
                    $this->renderRegisterView("Gagal: Dosen wajib mengisi NIP", $input_data);
                }
                // TAMBAHAN: Validasi Hanya Angka
                if (!ctype_digit($nomor_induk)) {
                    $this->renderRegisterView("Gagal: NIP harus berupa angka tanpa spasi/karakter lain", $input_data);
                }
            }
            else {
                $nomor_induk = null; 
            }

        // 5. PROSES KE DATABASE
        $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // PERBAIKAN DISINI: Menyiapkan array data untuk UserModel Class
        $dataRegister = [
            'username'    => $username,
            'nama'        => $nama_lengkap,
            'email'       => $email,
            'pass'        => $password_hash,
            'role'        => $role,
            'token'       => $otp,
            'nomor_induk' => $nomor_induk 
        ];

        // Panggil method createUser dari object userModel
        $result = $this->userModel->createUser($dataRegister);

        // 6. CEK HASIL
        if ($result === 'email_exists') {
            $this->renderRegisterView("Email sudah terdaftar.", $input_data);
        } elseif ($result === 'username_exists') {
            $this->renderRegisterView("Username sudah digunakan.", $input_data);
        } elseif ($result === 'nim_exists') {
            $this->renderRegisterView("NIM/NIP sudah terdaftar.", $input_data);
        } elseif ($result !== 'success') {
            $this->renderRegisterView("Gagal registrasi database. Error tidak diketahui.", $input_data);
        }

        // 7. SUKSES - Send OTP email and redirect to OTP page
        $this->sendOtpEmail($email, $nama_lengkap, $otp);
    }

    // --- HELPER FUNCTIONS ---

    private function renderRegisterView($pesan, $data = []) {
        $old_data = $data; 
        require 'app/views/register.php'; 
        exit(); 
    }

    private function sendOtpEmail($email, $nama, $otp) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sinergi.tik24@gmail.com';
            $mail->Password   = 'jzqzzlotalnaqqda';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('sinergi.tik24@gmail.com', 'PBL SINERGI');
            $mail->addAddress($email, $nama);
            $mail->isHTML(true);
            $mail->Subject = 'Kode OTP Verifikasi Akun SINERGI';
            $mail->CharSet = 'UTF-8';
            $mail->Body = $this->getOtpEmailTemplate($nama, $otp);

            $mail->send();
            // Store email in session for OTP verification page
            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_nama'] = $nama;
            header("Location: index.php?page=verify-otp");
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Registrasi sukses, tapi gagal kirim email OTP: " . $e->getMessage();
            header("Location: index.php?page=login");
        }
        exit();
    }

    private function getOtpEmailTemplate($nama, $otp) {
        $digits = str_split($otp);
        $otpBoxes = '';
        foreach ($digits as $digit) {
            $otpBoxes .= "<span style='display:inline-block;width:48px;height:56px;background:#111827;color:#fff;font-size:28px;font-weight:700;line-height:56px;text-align:center;border-radius:10px;margin:0 4px;font-family:monospace;'>{$digit}</span>";
        }
        
        return "
        <div style='font-family: Plus Jakarta Sans, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #111827 0%, #1f2937 100%); padding: 32px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 700;'>SINERGI</h1>
                <p style='color: #9ca3af; margin: 8px 0 0 0; font-size: 14px;'>Verifikasi Akun Anda</p>
            </div>
            
            <!-- Body -->
            <div style='padding: 40px 32px;'>
                <p style='color: #374151; font-size: 16px; margin: 0 0 8px 0;'>Halo <strong>{$nama}</strong>,</p>
                <p style='color: #6b7280; font-size: 15px; margin: 0 0 32px 0; line-height: 1.6;'>Terima kasih telah mendaftar di Sinergi! Gunakan kode OTP berikut untuk memverifikasi akun Anda:</p>
                
                <!-- OTP Code -->
                <div style='text-align: center; margin: 32px 0;'>
                    {$otpBoxes}
                </div>
                
                <!-- Timer Warning -->
                <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin: 24px 0;'>
                    <p style='color: #92400e; font-size: 14px; margin: 0;'>
                        <strong>Kode berlaku 5 menit</strong><br>
                        Jangan bagikan kode ini kepada siapapun.
                    </p>
                </div>
                
                <p style='color: #6b7280; font-size: 14px; margin: 24px 0 0 0;'>Jika Anda tidak merasa mendaftar di Sinergi, abaikan email ini.</p>
            </div>
            
            <!-- Footer -->
            <div style='background: #f9fafb; padding: 24px 32px; text-align: center; border-top: 1px solid #e5e7eb;'>
                <p style='color: #9ca3af; font-size: 12px; margin: 0;'>&copy; 2025 Sinergi Dev Team | Politeknik Negeri Jakarta</p>
            </div>
        </div>
        ";
    }

    public function showForgotPassword() {
        $pesan = $_SESSION['pesan'] ?? '';
        unset($_SESSION['pesan']);
        require_once 'app/views/forgot_password.php';
    }

    public function doForgotPassword() {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $_SESSION['pesan'] = "Email wajib diisi!";
            header("Location: index.php?page=forgot-password");
            exit();
        }

        $user = $this->userModel->getUserByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));

            if ($this->userModel->setResetToken($email, $token)) {
                $this->sendResetEmail($email, $user['nama_lengkap'], $token);
                $_SESSION['pesan'] = "Link reset password telah dikirim ke email Anda.";
            } else {
                $_SESSION['pesan'] = "Gagal update database. Cek koneksi Oracle.";
            }
        } else {
            $_SESSION['pesan'] = "Email tidak terdaftar.";
        }
        
        header("Location: index.php?page=forgot-password");
        exit();
    }

    private function sendResetEmail($email, $nama, $token) {
        $resetLink = "http://localhost/sinergi/index.php?page=reset-password&code=" . $token;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sinergi.tik24@gmail.com';
            $mail->Password   = 'jzqzzlotalnaqqda';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('sinergi.tik24@gmail.com', 'PBL SINERGI');
            $mail->addAddress($email, $nama);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Akun SINERGI';
            $mail->Body    = "
                <h3>Halo, $nama</h3>
                <p>Klik tombol di bawah untuk reset password:</p>
                <a href='$resetLink' style='background:#111827;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Reset Password</a>
                <br><br>
            ";
            $mail->send();
        } catch (Exception $e) {
            // Silent error
        }
    }

    // =====================================
    // FUNGSI RESET PASSWORD
    // =====================================

    public function showResetPassword() {
        $token = $_GET['code'] ?? '';
        $pesan = '';

        if (empty($token)) {
            $_SESSION['error_message'] = "Link reset password tidak valid. Token tidak ditemukan.";
            header("Location: index.php?page=login");
            exit();
        }

        $user = $this->userModel->getUserByToken($token);

        if (!$user) {
            $_SESSION['error_message'] = "Link reset password sudah tidak berlaku atau expired. Silakan request ulang.";
            header("Location: index.php?page=forgot-password");
            exit();
        }

        // Token valid - tampilkan form
        require_once 'app/views/reset_password.php';
    }

    public function doResetPassword() {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($password) || empty($confirm)) {
            $pesan = "Semua field wajib diisi!";
            require_once 'app/views/reset_password.php';
            exit();
        }

        if ($password !== $confirm) {
            $pesan = "Konfirmasi password tidak cocok!";
            require_once 'app/views/reset_password.php';
            exit();
        }

        if (strlen($password) < 6) { 
            $pesan = "Password minimal 6 karakter!";
            require_once 'app/views/reset_password.php';
            exit();
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $pesan = "Password harus mengandung minimal 1 huruf kapital!";
            require_once 'app/views/reset_password.php';
            exit();
        }
        if (!preg_match('/[0-9]/', $password)) {
            $pesan = "Password harus mengandung minimal 1 angka!";
            require_once 'app/views/reset_password.php';
            exit();
        }

        $user = $this->userModel->getUserByToken($token);
        if (!$user) {
            $_SESSION['error_message'] = "Token expired atau tidak valid. Silakan request ulang.";
            header("Location: index.php?page=forgot-password");
            exit();
        }

        $newHash = password_hash($password, PASSWORD_DEFAULT);

        if ($this->userModel->updateNewPassword($token, $newHash)) {
            $_SESSION['error_message'] = "Password berhasil diubah! Silakan login dengan password baru.";
            header("Location: index.php?page=login");
            exit();
        } else {
            $pesan = "Gagal mengubah password. Coba lagi nanti.";
            require_once 'app/views/reset_password.php';
            exit();
        }
    }
}
?>