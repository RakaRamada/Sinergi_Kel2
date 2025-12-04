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
            if ((int)$user_data['is_verif'] === 1) {
                $_SESSION['user_id']      = $user_data['user_id'];
                $_SESSION['nama_lengkap'] = $user_data['nama_lengkap'];
                $_SESSION['username']     = $user_data['username'];
                $_SESSION['role_name']    = $user_data['role_name'] ?? 'Mahasiswa';
                $_SESSION['role_id']      = $user_data['role_id']; 
                $_SESSION['avatar_url']   = !empty($user_data['avatar_url']) ? $user_data['avatar_url'] : '/Sinergi/public/assets/images/user.png';

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
        if (strlen($password_input) < 8) {
            $this->renderRegisterView("Password minimal harus 8 karakter!", $input_data);
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
        if ($role == '1') {
            if (!strpos($email, '@stu.pnj.ac.id')) {
                $this->renderRegisterView("Gagal: Mahasiswa wajib menggunakan email @stu.pnj.ac.id", $input_data);
            }
            if (empty($nomor_induk)) {
                $this->renderRegisterView("Gagal: Mahasiswa wajib mengisi NIM", $input_data);
            }
        } 
        else if ($role == '2') {
            if (!strpos($email, '@tik.pnj.ac.id')) {
                $this->renderRegisterView("Gagal: Dosen wajib menggunakan email @tik.pnj.ac.id", $input_data);
            }
            if (empty($nomor_induk)) {
                $this->renderRegisterView("Gagal: Dosen wajib mengisi NIP", $input_data);
            }
        }
        else {
            $nomor_induk = null; 
        }

        // 5. PROSES KE DATABASE
        $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(16));

        // PERBAIKAN DISINI: Menyiapkan array data untuk UserModel Class
        $dataRegister = [
            'username' => $username,
            'nama'     => $nama_lengkap,
            'email'    => $email,
            'pass'     => $password_hash,
            'role'     => $role,
            'token'    => $token,
            'nim'      => $nomor_induk
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

        // 7. SUKSES
        $this->sendVerificationEmail($email, $nama_lengkap, $token);
    }

    // --- HELPER FUNCTIONS ---

    private function renderRegisterView($pesan, $data = []) {
        $old_data = $data; 
        require 'app/views/register.php'; 
        exit(); 
    }

    private function sendVerificationEmail($email, $nama, $token) {
        $verifLink = "http://localhost/sinergi/index.php?page=verify&code=" . $token;
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
            $mail->Subject = 'Aktivasi Akun SINERGI Anda';
            $mail->Body = "Halo $nama,<br><br>Selamat datang di Sinergi. Silakan klik link berikut untuk mengaktifkan akun Anda:<br><br> 
                           <a href='{$verifLink}' style='background-color:black;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Verifikasi Akun</a>";

            $mail->send();
            $_SESSION['error_message'] = "Registrasi berhasil! Cek email untuk verifikasi.";
            header("Location: index.php?page=login");
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Registrasi sukses, tapi gagal kirim email verifikasi.";
            header("Location: index.php?page=login");
        }
        exit();
    }
}
?>