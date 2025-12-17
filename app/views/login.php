<?php
// app/views/login.php

// Mencegah error undefined variable jika file diakses langsung
$pesan = $pesan ?? '';
$old_email = $old_email ?? ''; // Menangkap email lama dari Controller

$old_pass = $_SESSION['temp_pass'] ?? '';
unset($_SESSION['temp_pass']);

// Pastikan koneksi tersedia
global $conn;
if (!isset($conn) || !$conn) {
    die("Koneksi GAGAL: Variabel \$conn tidak ditemukan. Pastikan HomeController.php sudah benar dan file config/koneksi.php berhasil.");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sinergi</title>
    <!-- Pastikan path CSS ini benar -->
    <link href="public/css/output.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    </style>
</head>

<body>
    <div class="flex h-screen">

        <!-- Bagian Gambar + Branding (Kiri) -->
        <div class="hidden md:flex md:w-3/5 relative">
            <!-- Background Image -->
            <img src="public/assets/images/Logo Siniger.jpg" alt="Login" class="h-full w-full object-cover">

            <!-- Overlay Gradient untuk readability -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>

            <!-- Branding Content -->
            <div class="absolute inset-0 flex flex-col justify-between p-12">
                <!-- Logo + Tagline di Bawah -->
                <div class="flex flex-col items-start">
                    <img src="/sinergi/public/assets/images/logo_sinergi_hitam.png" alt="Logo Sinergi"
                        class="h-16 w-auto mb-4 drop-shadow-2xl bg-white/90 px-4 py-2 rounded-xl">
                    <div class="text-white">
                        <h1 class="text-4xl font-bold mb-3 drop-shadow-lg">Sinergi</h1>
                        <p class="text-lg font-medium text-gray-100 drop-shadow-md max-w-md leading-relaxed">
                            Satu Platform, Sejuta Koneksi
                        </p>
                        <p class="text-sm text-gray-200 mt-3 drop-shadow-md max-w-lg leading-relaxed">
                            Ekosistem digital eksklusif mahasiswa untuk diskusi materi, berbagi pengetahuan, dan
                            membangun jaringan profesional.
                        </p>
                    </div>
                </div>

                <!-- Footer Branding -->
                <div class="text-white/80 text-sm">
                    <p class="drop-shadow-md">&copy; 2025 Sinergi Dev Team. Politeknik Negeri Jakarta</p>
                </div>
            </div>
        </div>

        <!-- Bagian Form (Kanan) -->
        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6 ">
                    Yuk mulai diskusimu!
                </h2>

                <!-- Alert Pesan Error/Sukses -->
                <?php 
                if (isset($pesan) && !empty($pesan)) : 
                    $is_error = strpos(strtolower($pesan), 'gagal') !== false || strpos(strtolower($pesan), 'salah') !== false || strpos(strtolower($pesan), 'verifikasi') === false;
                    $bg_color = $is_error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
                ?>
                <p class="<?= $bg_color ?> border px-4 py-3 rounded relative mb-4" role="alert">
                    <?= htmlspecialchars($pesan); ?>
                </p>
                <?php 
                endif; 
                ?>

                <!-- Form Login -->
                <form action="index.php?page=login-process" method="POST" class="space-y-4">

                    <!-- INPUT EMAIL (Flash Data diterapkan disini) -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required
                            value="<?= htmlspecialchars($old_email); ?>"
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    </div>

                    <!-- INPUT PASSWORD -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="Password" required
                                value="<?= htmlspecialchars($old_pass); ?>"
                                class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-gray-500">

                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer"
                                onclick="togglePasswordVisibility()">
                                <img id="togglePasswordIcon" src="public/assets/icons/eyeClosed.svg" alt="Toggle"
                                    class="w-5 h-5 text-gray-400">
                            </span>
                        </div>
                    </div>

                    <div class="text-right text-sm">
                        <a href="index.php?page=forgot-password"
                            class="font-semibold text-gray-600 hover:underline">Lupa password?</a>
                    </div>

                    <!-- CAPTCHA Section -->
                    <div class="flex items-end space-x-2">
                        <div class="w-2/3">
                            <label for="captcha_code" class="block text-sm font-medium text-gray-700 mb-1">Masukkan Kode
                                Captcha</label>
                            <input type="text" id="captcha_code" name="captcha_code" required
                                class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                placeholder="Masukkan Kode">
                        </div>
                        <div class="w-2/3 flex items-center space-x-2">
                            <!-- Gambar Captcha -->
                            <img src="index.php?page=captcha" alt="Captcha"
                                class="rounded-lg h-[50px] flex-1 object-cover border border-gray-300"
                                id="captcha_image">

                            <!-- Tombol Refresh -->
                            <button type="button" onclick="refreshCaptcha()"
                                class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 cursor-pointer"
                                title="Refresh Captcha">
                                <img src="public/assets/icons/refresh.svg" alt="refresh" class="w-6 h-6">
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login"
                        class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-opacity-50 cursor-pointer">
                        Sign In
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Belum punya akun?
                    <a href="index.php?page=register" class="font-semibold text-gray-600 hover:underline">Sign up.</a>
                </p>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.src = 'public/assets/icons/eyeOpen.svg';
        } else {
            passwordField.type = 'password';
            toggleIcon.src = 'public/assets/icons/eyeClosed.svg';
        }
    }

    function refreshCaptcha() {
        const captchaImage = document.getElementById('captcha_image');
        const timestamp = new Date().getTime();
        // Menambahkan timestamp agar browser tidak mengambil gambar dari cache
        captchaImage.src = 'index.php?page=captcha&t=' + timestamp;
    }
    </script>
</body>

</html>