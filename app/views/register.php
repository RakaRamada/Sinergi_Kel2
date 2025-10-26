<?php
// Pastikan path ini benar sesuai struktur folder Anda
require_once "../../config/koneksi.php";

$pesan = ""; // Variabel untuk menampung pesan notifikasi

if (isset($_POST['register'])) {
    // 1. Ambil data dari form
    $nama = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $code = md5($email . time());
    $is_verif = 0;

    // 2. Query SQL dengan variabel yang langsung dimasukkan ke dalamnya
    // PERHATIAN: Pastikan variabel string diapit oleh tanda kutip tunggal (')
    $sql_users = "INSERT INTO users (nama_lengkap, email, password, role, verifikasi_kode, is_verif) 
            VALUES ('$nama', '$email', '$password', '$role', '$code', $is_verif)";
            // Angka (seperti $is_verif) tidak perlu tanda kutip

    // 3. Persiapkan dan langsung eksekusi query
    $statement = oci_parse($conn, $sql);

    if (oci_execute($statement)) {
        $pesan = "✅ Pendaftaran berhasil. Silakan cek email untuk verifikasi.";
    } else {
        $e = oci_error($statement);
        if ($e['code'] == 1) { // Error jika email duplikat
            $pesan = "❌ Gagal mendaftar! Email ini sudah terdaftar.";
        } else {
            $pesan = "❌ Gagal mendaftar! Error: " . htmlentities($e['message']);
        }
    }

    // 4. Bebaskan resource
    oci_free_statement($statement);
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Pendaftaran</title>
</head>
<body>
    <h2>Form Pendaftaran</h2>
    <?php if (!empty($pesan)) : ?>
        <p class="pesan"><?php echo $pesan; ?></p>
    <?php endif; ?>
    <form method="post">
        <div>
            <label for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama_lengkap" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="mahasiswa">Mahasiswa</option>
                <option value="dosen">Dosen</option>
                <option value="mitra">Mitra</option>
                <option value="alumni">Alumni</option>
            </select>
        </div>
        <button type="submit" name="register">Daftar</button>
    </form>
</body>
</html>