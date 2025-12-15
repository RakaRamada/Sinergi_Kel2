<?php
// app/views/forgot_password.php
$pesan = $pesan ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sinergi</title>
    <link href="public/css/output.css" rel="stylesheet">
</head>

<body>
    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="public/assets/images/Logo Siniger.jpg" alt="Login" class="h-full w-full object-cover">
        </div>

        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Lupa Password?</h2>
                <p class="text-gray-600 mb-6">Masukkan email akun Anda, kami akan mengirimkan link untuk mereset
                    password.</p>

                <?php if (!empty($pesan)) : 
                    $is_error = strpos(strtolower($pesan), 'tidak') !== false || strpos(strtolower($pesan), 'gagal') !== false;
                    $bg_color = $is_error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
                ?>
                <p class="<?= $bg_color ?> border px-4 py-3 rounded relative mb-4" role="alert">
                    <?= htmlspecialchars($pesan); ?>
                </p>
                <?php endif; ?>

                <form action="index.php?page=forgot-password-process" method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" placeholder="Masukkan Email Anda" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button type="submit"
                        class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900">
                        Kirim Link Reset
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Kembali ke <a href="index.php?page=login"
                        class="font-semibold text-blue-600 hover:underline">Login</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>