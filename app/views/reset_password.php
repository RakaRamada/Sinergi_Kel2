<?php
// app/views/reset_password.php
$token = $token ?? $_GET['code'] ?? '';
$pesan = $pesan ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sinergi</title>
    <link href="public/css/output.css" rel="stylesheet">
</head>

<body>
    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="public/assets/images/Logo Siniger.jpg" alt="Login" class="h-full w-full object-cover">
        </div>

        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Buat Password Baru</h2>

                <?php if (!empty($pesan)) : 
                     $is_error = strpos(strtolower($pesan), 'gagal') !== false || strpos(strtolower($pesan), 'tidak') !== false || strpos(strtolower($pesan), 'minimal') !== false || strpos(strtolower($pesan), 'cocok') !== false;
                     $bg_color = $is_error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
                ?>
                <p class="<?= $bg_color ?> border px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($pesan); ?>
                </p>
                <?php endif; ?>

                <form action="index.php?page=reset-password-process" method="POST" class="space-y-4">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <input type="password" name="password" placeholder="Minimal 8 karakter, huruf besar & angka"
                            required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" placeholder="Ulangi Password Baru" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button type="submit"
                        class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800">
                        Simpan Password Baru
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