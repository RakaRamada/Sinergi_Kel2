<?php
require_once __DIR__ . '/../../config/koneksi.php';

// Pastikan variabel didefinisikan untuk menghindari warning error
$pesan = $pesan ?? ($_GET['pesan'] ?? '');
$old_data = $old_data ?? []; 
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran - Sinergi</title>
    <link href="/sinergi/public/css/output.css" rel="stylesheet">
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
            <img src="public/assets/images/Logo Siniger.jpg" alt="Registrasi" class="h-full w-full object-cover">

            <!-- Overlay Gradient untuk readability -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>

            <!-- Branding Content -->
            <div class="absolute inset-0 flex flex-col justify-between p-12">
                <!-- Logo + Tagline di Bawah -->
                <div class="flex flex-col items-start">
                    <img src="/sinergi/public/assets/images/logo_sinergi_hitam.png" alt="Logo Sinergi"
                        class="h-16 w-auto mb-4 drop-shadow-2xl bg-white/90 px-4 py-2 rounded-xl">
                    <div class="text-white">
                        <h1 class="text-4xl font-bold mb-3 drop-shadow-lg">Bergabung dengan Sinergi</h1>
                        <p class="text-lg font-medium text-gray-100 drop-shadow-md max-w-md leading-relaxed">
                            Mulai perjalanan digitalmu bersama ribuan mahasiswa
                        </p>
                        <div class="mt-6 space-y-3">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <p class="text-sm text-gray-200">Forum diskusi terarah berdasarkan mata kuliah</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <p class="text-sm text-gray-200">Berbagi materi dan sumber belajar berkualitas</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-green-400 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <p class="text-sm text-gray-200">Membangun networking dengan mahasiswa, dosen, dan
                                    alumni</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Branding -->
                <div class="text-white/80 text-sm">
                    <p class="drop-shadow-md">&copy; 2025 Sinergi Dev Team. Politeknik Negeri Jakarta</p>
                </div>
            </div>
        </div>

        <!-- Bagian Form (Kanan) -->
        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12 overflow-y-auto">

            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Buat akun dulu ya!</h2>

                <?php if (!empty($pesan)) : 
                    $is_error = strpos(strtolower($pesan), 'gagal') !== false || strpos(strtolower($pesan), 'sudah') !== false || strpos(strtolower($pesan), 'wajib') !== false || strpos(strtolower($pesan), 'password') !== false;
                    $bg_color = $is_error ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
                ?>
                <div class="<?php echo $bg_color; ?> border px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($pesan); ?>
                </div>
                <?php endif; ?>

                <form method="post" action="index.php?page=register-process" class="space-y-4" id="registerForm">

                    <!-- ROLE DROPDOWN -->
                    <!-- Logic: Cek apakah ada data lama, jika ada, select option yang sesuai -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Daftar Sebagai</label>
                        <select id="role" name="role" required onchange="updateFormRules()"
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <option value="1"
                                <?php echo (isset($old_data['role']) && $old_data['role'] == '1') ? 'selected' : ''; ?>>
                                Mahasiswa</option>
                            <option value="2"
                                <?php echo (isset($old_data['role']) && $old_data['role'] == '2') ? 'selected' : ''; ?>>
                                Dosen</option>
                            <option value="4"
                                <?php echo (isset($old_data['role']) && $old_data['role'] == '4') ? 'selected' : ''; ?>>
                                Mitra Industri</option>
                            <option value="3"
                                <?php echo (isset($old_data['role']) && $old_data['role'] == '3') ? 'selected' : ''; ?>>
                                Alumni</option>
                        </select>
                    </div>

                    <!-- NIM / NIP -->
                    <div id="nomor_induk_container">
                        <label for="nomor_induk" id="label_nomor_induk" class="sr-only">NIM</label>
                        <input type="text" id="nomor_induk" name="nomor_induk" placeholder="Masukkan NIM"
                            value="<?php echo htmlspecialchars($old_data['nomor_induk'] ?? ''); ?>"
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    </div>

                    <!-- USERNAME -->
                    <div>
                        <label for="username" class="sr-only">Username</label>
                        <input type="text" id="username" name="username" placeholder="Username"
                            value="<?php echo htmlspecialchars($old_data['username'] ?? ''); ?>" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    </div>

                    <!-- NAMA LENGKAP -->
                    <div>
                        <label for="nama_lengkap" class="sr-only">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Nama Lengkap Anda"
                            value="<?php echo htmlspecialchars($old_data['nama_lengkap'] ?? ''); ?>" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    </div>

                    <!-- EMAIL -->
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input type="email" id="email" name="email" placeholder="Email Kampus (@stu.pnj.ac.id)"
                            value="<?php echo htmlspecialchars($old_data['email'] ?? ''); ?>" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        <p id="email_helper" class="text-xs text-gray-500 mt-1 ml-1">Wajib menggunakan email
                            @stu.pnj.ac.id</p>
                    </div>

                    <!-- PASSWORD -->
                    <!-- Note: Password sengaja TIDAK di-refill (kosong) demi keamanan standar web -->
                    <div class="relative">
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" id="password" name="password" placeholder="Password" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        <p class="text-xs text-gray-500 mt-1 ml-1">Syarat: Min. 6 karakter, ada Huruf Besar & Angka</p>
                    </div>

                    <!-- KONFIRMASI PASSWORD -->
                    <div class="relative">
                        <label for="confirm_password" class="sr-only">Konfirmasi Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Konfirmasi Password" required
                            class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    </div>

                    <button type="submit" name="register"
                        class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-opacity-50 cursor-pointer">
                        Sign Up
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Sudah punya akun?
                    <a href="index.php?page=login" class="font-semibold text-gray-600 hover:underline">
                        Sign in.
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- SCRIPT LOGIKA TAMPILAN -->
    <script>
    function updateFormRules() {
        const role = document.getElementById('role').value;
        const nomorIndukContainer = document.getElementById('nomor_induk_container');
        const nomorIndukInput = document.getElementById('nomor_induk');
        const emailInput = document.getElementById('email');
        const emailHelper = document.getElementById('email_helper');

        // Reset display
        nomorIndukContainer.style.display = 'block';

        if (role == '1') { // MAHASISWA
            nomorIndukInput.placeholder = "Masukkan NIM";
            // Jangan reset value jika sudah ada isinya (dari PHP old_data)
            if (!nomorIndukInput.value) nomorIndukInput.value = "";

            emailInput.placeholder = "Email (@stu.pnj.ac.id)";
            emailHelper.innerText = "Wajib menggunakan email @stu.pnj.ac.id";
            emailHelper.className = "text-xs text-gray-600 mt-1 ml-1 font-medium";

        } else if (role == '2') { // DOSEN
            nomorIndukInput.placeholder = "Masukkan NIP";
            emailInput.placeholder = "Email (@tik.pnj.ac.id)";
            emailHelper.innerText = "Wajib menggunakan email @tik.pnj.ac.id";
            emailHelper.className = "text-xs text-gray-600 mt-1 ml-1 font-medium";

        } else { // ALUMNI & MITRA
            nomorIndukContainer.style.display = 'none';
            emailInput.placeholder = "Email Pribadi / Perusahaan";
            emailHelper.innerText = "Gunakan email pribadi/Perusahaan anda";
            emailHelper.className = "text-xs text-gray-500 mt-1 ml-1";
        }
    }

    // Jalankan saat load agar form menyesuaikan dengan data lama (old_data)
    document.addEventListener('DOMContentLoaded', updateFormRules);
    </script>
</body>

</html>