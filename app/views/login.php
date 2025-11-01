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
                        <label for="email" class="sr-only">Email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="relative">
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" id="password" name="password" placeholder="Password" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer"
                            onclick="togglePasswordVisibility()">
                            <img id="togglePasswordIcon" src="public/assets/icons/eyeClosed.svg"
                                alt="Toggle password visibility" class="w-5 h-5 text-gray-400">
                        </span>
                    </div>
                    <div class="text-right text-sm">
                        <a href="#" class="font-semibold text-blue-600 hover:underline">
                            Lupa password?
                        </a>
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
            toggleIcon.src = 'public/assets/icons/eyeOpen.svg';
        } else {
            passwordField.type = 'password';
            toggleIcon.src = 'public/Vpublic/assets/icons/eyeClosed.svg';
        }
    }
    </script>
</body>

</html>