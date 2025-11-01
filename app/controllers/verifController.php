<?php
// File: app/controllers/verifController.php

// 1. Panggil Model di luar fungsi
// Kita ganti path-nya agar konsisten dengan controller lain
require_once __DIR__ . '/../models/UserModel.php';

// 2. Bungkus SEMUA logika ke dalam fungsi verify_email()
function verify_email() {

    // 3. Mulai session (dipindahkan ke dalam fungsi)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 4. Cek apakah ada 'code' di URL
    // (Ini adalah 'LOGIKA UTAMA' dari file Anda sebelumnya)
    if (isset($_GET['code'])) {
        
        $token = $_GET['code'];

        // 5. Panggil fungsi Model (BUKAN koneksi.php)
        $result = verifyUserByToken($token);

        // 6. Tampilkan pesan berdasarkan hasil
        switch ($result) {
            case 'success':
                tampilkan_pesan(
                    'Verifikasi Berhasil!', 
                    'Akun Anda telah berhasil diverifikasi. Silakan kembali ke tab login Anda.', 
                    'sukses'
                );
                break;
                
            case 'invalid_or_expired':
                tampilkan_pesan(
                    'Token Tidak Valid', 
                    'Token verifikasi ini tidak valid, sudah kedaluwarsa, atau sudah digunakan sebelumnya.', 
                    'gagal'
                );
                break;
                
            case 'db_error':
                tampilkan_pesan(
                    'Verifikasi Gagal', 
                    'Terjadi kesalahan internal saat memproses verifikasi Anda. Coba lagi nanti atau hubungi admin.', 
                    'gagal'
                );
                break;
        }

    } else {
        // 7. Jika tidak ada token di URL
        tampilkan_pesan(
            'Link Error', 
            'Link verifikasi ini tidak lengkap atau salah.', 
            'gagal'
        );
    }
} // --- AKHIR FUNGSI verify_email() ---


/**
 * Fungsi untuk menampilkan halaman status verifikasi yang modern.
 * (Fungsi ini sekarang ada di luar verify_email(), tapi akan dipanggil olehnya)
 *
 * @param string $judul   Judul utama
 * @param string $pesan   Pesan detail
 * @param string $status  Status ('sukses', 'gagal', atau 'info')
 */
function tampilkan_pesan($judul, $pesan, $status = 'info') {
    
    // Path ke logo (Path ini dari index.php, jadi 'public/...' sudah benar)
    $logo_path = 'public/assets/images/logo.png'; 

    // Definisi Ikon SVG
    $icon_svg = '';
    if ($status == 'sukses') {
        $icon_svg = '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#28a745"/></svg>';
    } elseif ($status == 'gagal') {
        $icon_svg = '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#dc3545"/></svg>';
    } else {
        $icon_svg = '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="#007bff"/></svg>';
    }

    // Cetak Halaman HTML
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
            .container { background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); text-align: center; max-width: 480px; width: 90%; }
            .logo-container { display: flex; flex-direction: column; justify-content: center; align-items: center; margin-bottom: 24px; }
            .logo-container img { width: 50%; object-fit: cover; }
            .logo-container .brand-name { font-size: 28px; font-weight: 700; color: #343a40; margin-left: 0; margin-top: 12px; }
            .icon-container { margin-bottom: 20px; }
            h2 { font-size: 24px; font-weight: 600; color: #212529; margin-top: 0; margin-bottom: 16px; }
            .message-box { height: 120px; display: flex; align-items: center; justify-content: center; width: 100%; }
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
            <div class="icon-container">
                $icon_svg
            </div>
            <h2>$judul</h2>
            <div class="message-box">
                <p>$pesan</p>
            </div>
            <p class="footer">Anda bisa menutup tab ini sekarang. <br> <a href="index.php?page=login">Kembali ke Login</a></p>
        </div>
    </body>
    </html>
HTML;
    // Hentikan eksekusi setelah menampilkan pesan
    exit();
}

?>