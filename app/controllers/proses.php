<?php
require_once "../../config/koneksi.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php'; // pastikan composer sudah terinstall

if (isset($_POST['register'])) {
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $code = md5($email . time());
    $is_verif = 0;

    // Cek apakah email sudah terdaftar
    $check_sql = "SELECT * FROM users WHERE email = :email";
    $check_stmt = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stmt, ":email", $email);
    oci_execute($check_stmt);
    $existing = oci_fetch_assoc($check_stmt);

    if ($existing) {
    echo "<script>alert('Email sudah terdaftar!');window.location='../../index.php?page=register';</script>";
        exit();
    }

    // Masukkan data user ke tabel
    $sql = "INSERT INTO users (nama_lengkap, email, password, role, verifikasi_kode, is_verif)
            VALUES (:nama, :email, :password, :role, :code, :is_verif)";
    $stmt = oci_parse($conn, $sql);

    oci_bind_by_name($stmt, ":nama", $nama);
    oci_bind_by_name($stmt, ":email", $email);
    oci_bind_by_name($stmt, ":password", $password);
    oci_bind_by_name($stmt, ":role", $role);
    oci_bind_by_name($stmt, ":code", $code);
    oci_bind_by_name($stmt, ":is_verif", $is_verif);

    if (oci_execute($stmt)) {
        // Kirim email verifikasi
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sinergi.tik24@gmail.com'; // email pengirim
            $mail->Password   = 'jzqzzlotalnaqqda'; // app password Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('sinergi.tik24@gmail.com', 'PBL SINERGI');
            $mail->addAddress($email, $nama);

            $link = "http://localhost/sinergi/app/controllers/verifController.php?code=$code";
            $buttonStyle = "display: inline-block; " .
                       "padding: 12px 24px; " . // setara 'py-3 px-6'
                       "font-family: Arial, sans-serif; " .
                       "font-size: 16px; " . // setara 'text-base'
                       "font-weight: 600; " . // setara 'font-semibold'
                       "color: #ffffff; " . // setara 'text-white'
                       "background-color: #3b82f6; " . // setara 'bg-blue-500'
                       "border-radius: 8px; " . // setara 'rounded-lg'
                       "text-decoration: none;"; // setara 'no-underline'

        $mail->isHTML(true); // Pastikan ini true
        $mail->Subject = 'Verifikasi Akun SINERGI';
        
        // Gunakan style di dalam tag <a>
        $mail->Body    = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
            Hai $nama,<br><br>
            Terima kasih sudah mendaftar di platform SINERGI.<br>
            Klik tombol di bawah ini untuk memverifikasi akun kamu:<br><br>
            <a href='$link' style='$buttonStyle'>Verifikasi Akun Anda</a>
            <br><br>
            Salam,<br>Tim PBL SINERGI
        </div>
        ";
            $mail->send();
echo "<script>alert('Registrasi berhasil! Cek email kamu untuk verifikasi.');window.location='../../index.php?page=login';</script>";
        } catch (Exception $e) {
echo "<script>alert('Registrasi berhasil tapi email gagal dikirim. Error: {$mail->ErrorInfo}');window.location='../../index.php?page=login';</script>";        }
    } else {
        $e = oci_error($stmt);
echo "<script>alert('Gagal menyimpan data ke database! Error: " . htmlentities($e['message']) . "');window.location='../../index.php?page=register';</script>";    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>
