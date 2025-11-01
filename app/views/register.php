<?php
// Ambil pesan dari URL (jika ada)
$pesan = $_GET['pesan'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran - Sinergi</title>
    <link href="public/css/output.css" rel="stylesheet">
</head>

<body>
    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="public/assets/images/Logo Siniger.jpg" alt="Registrasi" class="h-full w-full object-cover">
        </div>

        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">

            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Buat akun dulu ya!</h2>

                <?php if (!empty($pesan)) : 
                    $is_error = strpos($pesan, 'gagal') !== false || strpos($pesan, 'sudah digunakan') !== false;
                    $bg_color = $is_error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
                ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($pesan); ?>
                </div>
                <?php endif; ?>


                <form method="post" action="index.php?page=register-process" class="space-y-4">
                    <div>
                        <label for="role" class="sr-only">Role</label>
                        <select id="role" name="role" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1" selected>Mahasiswa</option>
                            <option value="2">Dosen</option>
                            <option value="4">Mitra Industri</option>
                            <option value="3">Alumni</option>
                        </select>
                    </div>

                    <div>
                        <label for="username" class="sr-only">Username</label>
                        <input type="text" id="username" name="username" placeholder="Username (akan jadi ID unik)"
                            required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="nama_lengkap" class="sr-only">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap Anda"
                            required
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
                    </div>

                    <div class="relative">
                        <label for="confirm_password" class="sr-only">Konfirmasi Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Konfirmasi Password" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
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