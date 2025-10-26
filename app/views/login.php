<?php
session_start();
// Pastikan path ini benar sesuai struktur folder Anda
require_once "../../config/koneksi.php";

$pesan = ""; // Variabel untuk menampung pesan notifikasi

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password_input = $_POST['password']; // Password dari form login

    // 1. Query untuk mencari user berdasarkan EMAIL saja
    $sql = "SELECT * FROM users WHERE email = '$email'";

    // 2. Persiapkan dan eksekusi query
    $statement = oci_parse($conn, $sql);
    oci_execute($statement);

    // 3. Ambil data user dari database
    // oci_fetch_assoc akan menghasilkan false jika tidak ada data yang ditemukan
    $user_data = oci_fetch_assoc($statement);

    // 4. Cek apakah user dengan email tersebut ditemukan
    if ($user_data) {
        // Jika user ditemukan, sekarang verifikasi passwordnya
        // $user_data['PASSWORD'] -> mengambil hash password dari database
        // $password_input -> password yang diketik user di form
        if (password_verify($password_input, $user_data['PASSWORD'])) {
            
            // Cek apakah akun sudah diverifikasi
            // PERHATIAN: Kolom di Oracle seringkali menjadi uppercase, jadi kita pakai 'IS_VERIF'
            if ($user_data['IS_VERIF'] == 1) {
                $_SESSION['user'] = $user_data;
                echo "<script>alert('Login Berhasil!');window.location='../../index.php';</script>";
                exit; // Hentikan eksekusi setelah redirect
            } else {
                $pesan = "Verifikasi akun anda terlebih dahulu!";
            }
        } else {
            // Jika password tidak cocok
            $pesan = "Email atau Password salah!";
        }
    } else {
        // Jika email tidak ditemukan di database
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
    <title>Form Login</title>
</head>
<body>
    <h2>Form Login</h2>

    <?php if (!empty($pesan)) : ?>
        <p class="pesan"><?php echo $pesan; ?></p>
    <?php endif; ?>
    
    <form action="" method="post">
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" name="login">Login</button>
        <p>Belum punya akun? <a href="register.php">Daftar disini!</a></p>
    </form>
</body>
</html>