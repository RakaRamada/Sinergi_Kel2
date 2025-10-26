<?php
session_start();
require_once "../../config/koneksi.php";

$pesan = ""; 

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password_input = $_POST['password']; 

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $statement = oci_parse($conn, $sql);
    oci_execute($statement);

    $user_data = oci_fetch_assoc($statement);

    if ($user_data) {
        if (password_verify($password_input, $user_data['PASSWORD'])) {
            
            if ($user_data['IS_VERIF'] == 1) {
                $_SESSION['user'] = $user_data;
                echo "<script>alert('Login Berhasil!');window.location='../../index.php';</script>";
                exit; 
            } else {
                $pesan = "Verifikasi akun anda terlebih dahulu!";
            }
        } else {
            $pesan = "Email atau Password salah!";
        }
    } else {
        $pesan = "Email atau Password salah!";
    }

    oci_free_statement($statement);
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>

    <div class="flex h-screen">
        <div class="hidden md:block md:w-3/5">
            <img src="../../public/assets/images/Logo Siniger.jpg" alt="Login" class="h-full w-full object-cover">
        </div>

        <div class="w-full bg-white md:w-2/5 flex flex-col justify-center items-center p-8 md:p-12">
            
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">
                    Yuk mulai diskusimu!
                </h2>

                <?php if (!empty($pesan)) : ?>
                    <p class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <?php echo $pesan; ?>
                    </p>
                <?php endif; ?>
                
                <form action="" method="post" class="space-y-4">
                    
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="relative">
                        <label for="password" class="sr-only">Password</label>
                        <input type="password" id="password" name="password" placeholder="Password" required 
                               class="w-full border border-gray-300 rounded-lg py-3 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePasswordVisibility()">
                            <img id="togglePasswordIcon" 
                                        src="../../public/assets/icons/eyeOpen.svg" 
                                        alt="Toggle password visibility" 
                                        class="w-5 h-5 text-gray-400">
                                             <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                    </div>

                    <div class="text-right text-sm">
                        <a href="#" class="font-semibold text-blue-600 hover:underline">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" name="login" 
                            class="w-full bg-gray-900 text-white font-bold rounded-lg py-3 mt-4 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-opacity-50">
                        Sign In
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Belum punya akun? 
                    <a href="register.php" class="font-semibold text-blue-600 hover:underline">
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
            // Jika 'password', ubah ke 'text'
            passwordField.type = 'text';
            
            // Ubah 'src' ikon menjadi mata TERBUKA
            toggleIcon.src = '../../public/assets/icons/eyeOpen.svg';
            
        } else {
            // Jika 'text', ubah kembali ke 'password'
            passwordField.type = 'password';
            
            // Ubah 'src' ikon menjadi mata TERTUTUP
            toggleIcon.src = '../../public/assets/icons/eyeClosed.svg';
        }
    }
        
    </script>

</body>
</html>