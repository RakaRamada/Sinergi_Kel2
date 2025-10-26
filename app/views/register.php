<?php
require_once "../../config/koneksi.php";

$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <?php if (!empty($pesan)) : ?>
        <p style="color: green;"><?php echo htmlspecialchars($pesan); ?></p>
    <?php endif; ?>

    <form method="post" action="../controllers/proses.php">
    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="../../public/assets/images/Logo Siniger.jpg" alt="Registrasi" class="h-full w-full object-cover">
        </div>

        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            
        <div class="w-full max-w-md">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Buat akun dulu ya!</h2>

        <form method="post" action="../controllers/proses.php" class="space-y-4">     
            <div>
                <label for="role" class="sr-only">Role</label>
                    <select id="role" name="role" required 
                        class="w-full border border-gray-300 rounded-lg py-3 px-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="mahasiswa" selected>Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                        <option value="mitra">Mitra</option>
                        <option value="alumni">Alumni</option>
                        </select>
                    </div>

                    <div>
                        <label for="nama" class="sr-only">Username</label>
                        <input type="text" id="nama" name="nama_lengkap" placeholder="Username" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="relative">
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" id="password" name="password" placeholder="Password" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                    </div>

                    <button type="submit" name="register" 
                            class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-opacity-50">
                        Sign Up
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Sudah punya akun? 
                    <a href="login.php" class="font-semibold text-blue-600 hover:underline">
                        Sign in.
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
