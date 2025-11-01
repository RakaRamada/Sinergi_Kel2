<?php
// File: app/controllers/AuthController.php

// Panggil semua yang dibutuhkan
require_once __DIR__ . '/../models/UserModel.php'; 

// Untuk PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php'; 

// Mulai sesi (Pastikan sudah ada di index.php, tapi jaga-jaga)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// =====================================
// FUNGSI LOGIN
// =====================================

/**
 * Menampilkan halaman Login.
 */
function showLogin() {
    // Pesan error dari proses login/register sebelumnya
    $pesan = $_SESSION['error_message'] ?? '';
    unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan
    
    // Path view disesuaikan: app/views/login.php
    require_once 'app/views/login.php'; 
}

/**
 * Memproses data dari form login (Menggunakan Email).
 */
function doLogin() {
    
    // 1. Ambil data dari form
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? ''; 

    if (empty($email) || empty($password_input)) {
        $_SESSION['error_message'] = "Email dan password wajib diisi!";
        header("Location: index.php?page=login");
        exit(); 
    }

    // 2. Panggil Model untuk mencari user berdasarkan EMAIL
    // Pastikan fungsi getUserByEmail() sudah ada di UserModel.php
    $user_data = getUserByEmail($email); 
    
    if ($user_data) {
        // 3. Verifikasi password
        if (password_verify($password_input, $user_data['password_hash'])) { // Ganti ke nama kolom password_hash
            
            // 4. Cek status verifikasi
            if ((int)($user_data['is_verified'] ?? 0) === 1) { // Kolom 'is_verified'
                // Login Berhasil
                $_SESSION['user_id'] = $user_data['user_id'];
                $_SESSION['nama'] = $user_data['nama_lengkap'];
                $_SESSION['role_name'] = $user_data['role_name']; 
                
                header("Location: index.php?page=dashboard");
                exit; 
            } else {
                $_SESSION['error_message'] = "Verifikasi akun anda terlebih dahulu! Cek email Anda.";
            }
        } else {
            $_SESSION['error_message'] = "Email atau Password salah!";
        }
    } else {
        $_SESSION['error_message'] = "Email atau Password salah!";
    }

    // Jika sampai sini, login gagal. Arahkan kembali ke halaman login
    header("Location: index.php?page=login");
    exit();
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

/**
 * Menampilkan halaman Registrasi.
 */
function showRegister() {
    // Ambil pesan dari URL (jika ada)
    $pesan = $_GET['pesan'] ?? '';
    
    // Path view disesuaikan: app/views/register.php
    require_once 'app/views/register.php';
}

/**
 * Memproses registrasi pengguna baru.
 */
function doRegister() {
    $username = trim($_POST['username'] ?? ''); // tambahkan field username di form
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '3';
    
    $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));
    
    // panggilan createUser() diperbaiki:
    // HAPUS $expires dari panggilan ini
    $result = createUser($username, $nama_lengkap, $email, $password_hash, $role, $token);


    // 4️⃣ Tangani hasil dari model
    if ($result === 'email_exists') {
        $pesan = "Email sudah terdaftar. Silakan login.";
        header("Location: index.php?page=register&pesan=" . urlencode($pesan));
        exit();
    } elseif ($result === 'db_error') {
        $pesan = "Gagal menyimpan data ke database. Coba lagi nanti.";
        header("Location: index.php?page=register&pesan=" . urlencode($pesan));
        exit();
    } elseif ($result !== 'success') {
        $pesan = "Terjadi kesalahan tidak diketahui.";
        header("Location: index.php?page=register&pesan=" . urlencode($pesan));
        exit();
    }

    // 5️⃣ Kirim email verifikasi jika berhasil
    $verifLink = "http://localhost/sinergi/index.php?page=verify&code=" . $token;

    $mail = new PHPMailer(true);
    try {
        // SMTP Setup
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sinergi.tik24@gmail.com';
        $mail->Password   = 'jzqzzlotalnaqqda';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Email pengirim & penerima
        $mail->setFrom('sinergi.tik24@gmail.com', 'PBL SINERGI');
        $mail->addAddress($email, $nama_lengkap);

        // Tombol gaya inline
        $buttonStyle = "display:inline-block;padding:12px 24px;font-family:Arial,sans-serif;
                        font-size:16px;font-weight:600;color:#fff;background-color:#3b82f6;
                        border-radius:8px;text-decoration:none;";

        $mail->isHTML(true);
        $mail->Subject = 'Aktivasi Akun SINERGI Anda';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                Hai $nama_lengkap,<br><br>
                Terima kasih sudah mendaftar di platform SINERGI.<br>
                Silakan klik tombol di bawah ini untuk memverifikasi akun kamu:<br><br>
                <a href='{$verifLink}' style='{$buttonStyle}'>Verifikasi Akun Anda</a>
                <br><br>
                Jika Anda tidak mendaftar, abaikan email ini.<br><br>
                Salam,<br>Tim PBL SINERGI
            </div>
        ";
        $mail->AltBody = "Halo {$nama_lengkap},\n\nSilakan salin dan tempel link berikut untuk aktivasi:\n{$verifLink}\n\nSalam,\nTim SINERGI";

        $mail->send();

        $_SESSION['error_message'] = "Registrasi berhasil! Silakan cek email kamu untuk verifikasi akun.";
        header("Location: index.php?page=login");
        exit();

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        $_SESSION['error_message'] = "Registrasi berhasil, tapi gagal kirim email verifikasi. Error: {$mail->ErrorInfo}";
        header("Location: index.php?page=login");
        exit();
    }
}