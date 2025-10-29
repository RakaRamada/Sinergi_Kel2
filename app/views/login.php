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
    <title>Login</title>
    <link href="/Sinergi/public/css/output.css" rel="stylesheet">
</head>
<body>
    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="/Sinergi/public/assets/images/Logo Siniger.jpg" alt="Login" class="h-full w-full object-cover">
        </div>

        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">
                    Yuk mulai diskusimu!
                </h2>

                <?php if (!empty($pesan)) : ?>
                    <p class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <?= htmlspecialchars($pesan) ?>
                    </p>
                <?php endif; ?>
                
                <!-- 
                  === PERBAIKAN PENTING ===
                  'action' sekarang menunjuk ke 'process-login'
                  Ini akan memanggil fungsi process_login() di HomeController Anda
                -->
                <form action="index.php?page=process-login" method="post" class="space-y-4">
                    <div>
                        <input type="email" id="email" name="email" placeholder="Email" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Password" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePasswordVisibility()">
                            <img id="togglePasswordIcon" 
                                 src="/Sinergi/public/assets/icons/eyeOpen.svg" 
                                 alt="Toggle password visibility" 
                                 class="w-5 h-5 text-gray-400">
                        </span>
                    </div>

                    <button type="submit" name="login" 
                            class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-opacity-50">
                        Sign In
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Belum punya akun? 
                    <a href="index.php?page=register" class="font-semibold text-blue-600 hover:underline">
                        Sign up.
                    </a>
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
                toggleIcon.src = '/Sinergi/public/assets/icons/eyeClosed.svg';
            } else {
                passwordField.type = 'password';
                toggleIcon.src = '/Sinergi/public/assets/icons/eyeOpen.svg';
            }
        }
    </script>
</body>
</html>
