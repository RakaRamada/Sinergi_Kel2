<?php
require_once __DIR__ . '/../../config/koneksi.php';
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran</title>
    <link href="/Sinergi/public/css/output.css" rel="stylesheet">
</head>
<body>
    <?php if (!empty($pesan)) : ?>
        <p class="text-center bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?= htmlspecialchars($pesan) ?>
        </p>
    <?php endif; ?>

    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="/Sinergi/public/assets/images/Logo Siniger.jpg" alt="Registrasi" class="h-full w-full object-cover">
        </div>

        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Buat akun dulu ya!</h2>

                <form method="post" action="app/controllers/proses.php" class="space-y-4">     
                    <div>
                        <select id="role" name="role" required 
                                class="w-full border border-gray-300 rounded-lg py-3 px-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="mahasiswa" selected>Mahasiswa</option>
                            <option value="dosen">Dosen</option>
                            <option value="mitra">Mitra</option>
                            <option value="alumni">Alumni</option>
                        </select>
                    </div>

                    <div>
                        <input type="text" id="nama" name="nama_lengkap" placeholder="Username" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <input type="email" id="email" name="email" placeholder="Email" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Password" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                            <img src="/Sinergi/public/assets/icons/eyeOpen.svg" alt="Toggle password visibility" class="w-5 h-5 text-gray-400">
                        </span>
                    </div>

                    <button type="submit" name="register" 
                            class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-opacity-50">
                        Sign Up
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Sudah punya akun? 
                    <a href="index.php?page=login" class="font-semibold text-blue-600 hover:underline">
                        Sign in.
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
