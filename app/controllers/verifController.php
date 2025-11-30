<?php
// File: app/controllers/verifController.php

require_once __DIR__ . '/../models/UserModel.php';

/**
 * Fungsi utama untuk memverifikasi akun berdasarkan kode/token
 */
function verify_email() {

    // Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Cek apakah ada 'code' di URL
    if (isset($_GET['code'])) {
        $token = $_GET['code'];

        // Coba pakai fungsi dari Model (versi Rifky)
        if (function_exists('verifyUserByToken')) {
            $result = verifyUserByToken($token);
        } else {
            // Jika model belum ada, gunakan logika manual (versi Raka)
            require_once __DIR__ . '/../../config/koneksi.php';

            $sql_select = "SELECT * FROM users WHERE verifikasi_kode = :code";
            $stmt_select = oci_parse($conn, $sql_select);
            oci_bind_by_name($stmt_select, ":code", $token);
            oci_execute($stmt_select);
            $user = oci_fetch_assoc($stmt_select);

            if ($user) {
                if ($user['IS_VERIF'] == 1) {
                    $result = 'already_verified';
                } else {
                    $sql_update = "UPDATE users SET is_verif = 1 WHERE verifikasi_kode = :code";
                    $stmt_update = oci_parse($conn, $sql_update);
                    oci_bind_by_name($stmt_update, ":code", $token);
                    if (oci_execute($stmt_update)) {
                        $result = 'success';
                    } else {
                        $result = 'db_error';
                    }
                    oci_free_statement($stmt_update);
                }
            } else {
                $result = 'invalid_or_expired';
            }

            oci_free_statement($stmt_select);
            oci_close($conn);
        }

        // --- Hasil Akhir Verifikasi ---
        switch ($result) {
            case 'success':
                tampilkan_pesan(
                    'Verifikasi Berhasil!',
                    'Akun Anda telah berhasil diverifikasi. Silakan login.',
                    'sukses'
                );
                break;

            case 'already_verified':
                tampilkan_pesan(
                    'Sudah Diverifikasi',
                    'Akun ini sudah pernah diverifikasi sebelumnya.',
                    'info'
                );
                break;

            case 'invalid_or_expired':
                tampilkan_pesan(
                    'Kode Tidak Valid',
                    'Kode verifikasi ini tidak valid, sudah kedaluwarsa, atau tidak ditemukan.',
                    'gagal'
                );
                break;

            case 'db_error':
                tampilkan_pesan(
                    'Terjadi Kesalahan',
                    'Verifikasi gagal karena kesalahan sistem. Coba lagi nanti.',
                    'gagal'
                );
                break;

            default:
                tampilkan_pesan(
                    'Verifikasi Gagal',
                    'Terjadi kesalahan tak terduga.',
                    'gagal'
                );
                break;
        }

    } else {
        tampilkan_pesan(
            'Link Error',
            'Link verifikasi tidak lengkap atau salah.',
            'gagal'
        );
    }
}

/**
 * Fungsi untuk menampilkan halaman status verifikasi (versi Rifky)
 */
function tampilkan_pesan($judul, $pesan, $status = 'info') {
    $logo_path = 'public/assets/images/logo.png';

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
?>
