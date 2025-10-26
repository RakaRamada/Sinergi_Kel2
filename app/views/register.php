<?php
require_once "../../config/koneksi.php";

$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Pendaftaran</title>
</head>
<body>
    <h2>Form Pendaftaran Akun SINERGI</h2>

    <?php if (!empty($pesan)) : ?>
        <p style="color: green;"><?php echo htmlspecialchars($pesan); ?></p>
    <?php endif; ?>

    <form method="post" action="../controllers/proses.php">
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
