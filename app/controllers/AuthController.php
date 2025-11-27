<?php
// File: app/controllers/AuthController.php (FINAL FIXED)

require_once __DIR__ . '/../models/UserModel.php'; 

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================
// FUNGSI LOGIN
// =====================================

function showLogin() {
    $pesan = $_SESSION['error_message'] ?? '';
    unset($_SESSION['error_message']);
    require_once 'app/views/login.php'; 
}

function doLogin() {
    // 1. INISIALISASI VARIABEL DI AWAL (Mencegah Undefined Variable)
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $captcha_input = $_POST['captcha_code'] ?? '';
    $captcha_session = $_SESSION["code"] ?? '';

    // 2. Cek CAPTCHA
    if (empty($captcha_input)) {
        $_SESSION['error_message'] = "CAPTCHA tidak boleh kosong!";
        header("Location: index.php?page=login");
        exit(); 
    }
    
    if ($captcha_session != $captcha_input) {
        $_SESSION['error_message'] = "Kode CAPTCHA salah!";
        header("Location: index.php?page=login");
        exit();
    }
    
    // 3. Validasi Input Kosong
    if (empty($email) || empty($password_input)) {
        $_SESSION['error_message'] = "Email dan password wajib diisi!";
        header("Location: index.php?page=login");
        exit(); 
    }

    // 4. Cari User di Database
    $user_data = getUserByEmail($email); 
    
    // 5. Cek Password
    // Pastikan $user_data TIDAK NULL sebelum akses array-nya
    if ($user_data && isset($user_data['password']) && password_verify($password_input, $user_data['password'])) {
            
        $is_verif = $user_data['is_verif'] ?? 0; 

        if ((int)$is_verif === 1) {
            // --- LOGIN SUKSES ---
            $_SESSION['user_id']      = $user_data['user_id'];
            $_SESSION['nama_lengkap'] = $user_data['nama_lengkap'];
            $_SESSION['username']     = $user_data['username'];
            $_SESSION['role_name']    = $user_data['role_name'] ?? 'Mahasiswa'; 
            $_SESSION['role_id']      = $user_data['role_id']; // PENTING

            $_SESSION['avatar_url']   = !empty($user_data['avatar_url']) 
                                        ? $user_data['avatar_url'] 
                                        : '/Sinergi/public/assets/images/default_avatar.png';

            // Redirect sesuai Role
            if ($user_data['role_id'] == 5) {
                header("Location: index.php?page=admin-dashboard");
            } else {
                header("Location: index.php?page=dashboard");
            }
            exit(); 

        } else {
            $_SESSION['error_message'] = "Akun belum diverifikasi. Cek email Anda.";
            header("Location: index.php?page=login");
            exit();
        }
    } else {
        // Gagal Login
        $_SESSION['error_message'] = "Email atau Password salah!";
        header("Location: index.php?page=login");
        exit();
    }
}

// =====================================
// FUNGSI LOGOUT
// =====================================

function logout() {
    session_unset();
    session_destroy();
    header("Location: index.php?page=login");
    exit();
}

// =====================================
// FUNGSI REGISTRASI
// =====================================

function showRegister() {
    $pesan = $_GET['pesan'] ?? '';
    require_once 'app/views/register.php';
}

function doRegister() {
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '1'; 
    
    if (empty($username) || empty($nama_lengkap) || empty($email) || empty($password_input)) {
        $pesan = "Semua field wajib diisi!";
        header("Location: index.php?page=register&pesan=" . urlencode($pesan));
        exit();
    }
    
    $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));
    
    $result = createUser($username, $nama_lengkap, $email, $password_hash, $role, $token);

    if ($result === 'email_exists') {
        $pesan = "Email sudah terdaftar.";
        header("Location: index.php?page=register&pesan=" . urlencode($pesan));
        exit();
    } elseif ($result === 'username_exists') {
        $pesan = "Username sudah digunakan.";
        header("Location: index.php?page=register&pesan=" . urlencode($pesan));
        exit();
    } elseif ($result !== 'success') {
        $pesan = "Gagal registrasi database.";
        header("Location: index.php?page=register&pesan=" . urlencode($pesan));
        exit();
    }

    // Kirim Email
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
        $mail->addAddress($email, $nama_lengkap);
        $mail->isHTML(true);
        $mail->Subject = 'Aktivasi Akun SINERGI Anda';
        $mail->Body = "Klik <a href='{$verifLink}'>disini</a> untuk verifikasi.";

        $mail->send();
        $_SESSION['error_message'] = "Registrasi berhasil! Cek email untuk verifikasi.";
        header("Location: index.php?page=login");
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Registrasi sukses, tapi gagal kirim email.";
        header("Location: index.php?page=login");
    }
    exit();
}
?>