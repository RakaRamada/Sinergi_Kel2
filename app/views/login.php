<?php
// login.php sekarang menjadi "View" murni.
// Ia tidak lagi memproses login, hanya menampilkan form.

// Ambil pesan error dari session (yang di-set oleh process_login)
if (isset($_SESSION['error'])) {
    $pesan = $_SESSION['error'];
    // Hapus pesan error setelah ditampilkan agar tidak muncul lagi
    unset($_SESSION['error']);
} else {
    $pesan = "";
}

// Variabel $conn sudah otomatis ada dari HomeController.php
// Kita bisa cek jika $conn benar-benar ada
if (!$conn) {
    die("Koneksi gagal dimuat dari Controller.");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sinergi</title>
    <link href="public/css/output.css" rel="stylesheet">
</head>

<body>
    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="public/assets/images/Logo Siniger.jpg" alt="Login" class="h-full w-full object-cover">
        </div>
        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">
                    Yuk mulai diskusimu!
                </h2>
                
                <?php 
                if (isset($pesan) && !empty($pesan)) : 
                    // Tentukan warna berdasarkan konten pesan
                    $is_error = strpos(strtolower($pesan), 'gagal') !== false || strpos(strtolower($pesan), 'salah') !== false || strpos(strtolower($pesan), 'verifikasi') === false;
                    $bg_color = $is_error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
                ?>
                <p class="<?= $bg_color ?> border px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($pesan); ?>
                </p>
                <?php 
                endif; 
                ?>
                <form action="index.php?page=login-process" method="POST" class="space-y-4">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="Password" required
                                class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer"
                                onclick="togglePasswordVisibility()">
                                <img id="togglePasswordIcon" src="public/assets/icons/eyeClosed.svg"
                                    alt="Toggle password visibility" class="w-5 h-5 text-gray-400">
                            </span>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <a href="#" class="font-semibold text-blue-600 hover:underline">
                            Lupa password?
                        </a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="w-2/3">
                            <label for="captcha_code" class="block text-sm font-medium text-gray-700 mb-1">Masukkan Kode
                                Captcha</label>
                            <input type="text" id="captcha_code" name="captcha_code" required
                                class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Masukkan Kode">
                        </div>

                        <div class="w-1/3 mt-4 flex items-center space-x-2">

                            <img src="index.php?page=captcha" alt="Captcha"
                                class="rounded-lg h-[50px] flex-1 object-cover border border-gray-300"
                                id="captcha_image"> <button type="button" onclick="refreshCaptcha()"
                                class="p-3 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                title="Refresh Captcha">
                                <img src="/Sinergi/public/assets/icons/refresh.svg" alt="refresh" class="w-5 h-5">
                            </button>

                        </div>
                    </div>


                    <button type="submit" name="login"
                        class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-opacity-50">
>>>>>>> 00fa24f135bcb4eb5f2eb523ef20a12f45df9fa1
                        Sign In
                    </button>
                </form>
                <p class="text-center text-sm text-gray-600 mt-6">
<<<<<<< HEAD
                    Belum punya akun? 
=======
                    Belum punya akun?
>>>>>>> 00fa24f135bcb4eb5f2eb523ef20a12f45df9fa1
                    <a href="index.php?page=register" class="font-semibold text-blue-600 hover:underline">
                        Sign up.
                    </a>
                </p>
            </div>
        </div>
    </div>
    <script>
<<<<<<< HEAD
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.src = '/Sinergi/public/assets/icons/eyeClosed.svg';
            } else {
                passwordField.type = 'password';
                toggleIcon.src = '/Sinergi/public/assets/icons/eyeOpen.svg';
            }
        }
    </script>
</body>
</html>
=======
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

    // FUNGSI BARU UNTUK REFRESH CAPTCHA
    function refreshCaptcha() {
        // 1. Ambil elemen gambar captcha berdasarkan ID-nya
        const captchaImage = document.getElementById('captcha_image');

        // 2. Buat timestamp unik (ini trik 'cache-busting')
        const timestamp = new Date().getTime();

        // 3. Set 'src' gambar ke URL yang sama + parameter timestamp
        // Ini memaksa browser meminta gambar baru ke server, bukan dari cache
        captchaImage.src = 'index.php?page=captcha&t=' + timestamp;
    }
    </script>
</body>

</html>
>>>>>>> 00fa24f135bcb4eb5f2eb523ef20a12f45df9fa1
